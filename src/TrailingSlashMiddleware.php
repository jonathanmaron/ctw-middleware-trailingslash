<?php
declare(strict_types=1);

namespace Ctw\Middleware\TrailingSlashMiddleware;

use Fig\Http\Message\StatusCodeInterface;
use Middlewares\Utils\Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TrailingSlashMiddleware extends AbstractTrailingSlashMiddleware
{
    /**
     * @var string
     */
    private const string HEADER = 'Location';

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $config = $this->getConfig();
        $uri    = $request->getUri();

        // Check for disabled paths
        if (isset($config['path_disable'])) {
            foreach ($config['path_disable'] as $path) {
                if (str_starts_with($uri->getPath(), $path)) {
                    return $handler->handle($request);
                }
            }
        }

        // Check if a trailing slash needs to be added BEFORE processing request
        $normalizedPath = $this->normalize($uri->getPath());

        if ($normalizedPath !== $uri->getPath()) {
            // Need to redirect - do it immediately without processing the request
            $location = $uri->withPath($normalizedPath)->__toString();
            $factory  = Factory::getResponseFactory();
            $response = $factory->createResponse(StatusCodeInterface::STATUS_MOVED_PERMANENTLY);

            return $response->withHeader(self::HEADER, $location);
        }

        // Path is already normalized, continue with the request
        return $handler->handle($request);
    }

    private function normalize(string $path): string
    {
        $slash = '/';

        if ('' === $path) {
            return $slash;
        }

        if (1 < strlen($path)) {
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            if ($slash !== substr($path, -1) && '' === $extension) {
                return $path . $slash;
            }
        }

        return $path;
    }
}

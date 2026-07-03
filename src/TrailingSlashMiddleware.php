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
    private const string HEADER = 'Location';

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $config = $this->getConfig();
        $uri    = $request->getUri();
        $path   = $uri->getPath();

        // Check for disabled paths: skip normalization when any configured prefix matches.
        $pathDisable = $config['path_disable'] ?? null;
        if (is_array($pathDisable) && array_any(
            $pathDisable,
            static fn (mixed $prefix): bool => is_string($prefix) && str_starts_with($path, $prefix),
        )) {
            return $handler->handle($request);
        }

        // Check if a trailing slash needs to be added BEFORE processing request
        $normalizedPath = $this->normalize($path);

        if ($normalizedPath !== $path) {
            // Need to redirect - do it immediately without processing the request
            $location = $uri->withPath($normalizedPath)
                ->__toString();
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
            if (!str_ends_with($path, $slash) && '' === $extension) {
                return $path . $slash;
            }
        }

        return $path;
    }
}

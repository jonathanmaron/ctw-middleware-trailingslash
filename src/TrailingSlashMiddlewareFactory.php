<?php
declare(strict_types=1);

namespace Ctw\Middleware\TrailingSlashMiddleware;

use Psr\Container\ContainerInterface;

class TrailingSlashMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): TrailingSlashMiddleware
    {
        $config = [];
        if ($container->has('config')) {
            $config = $container->get('config');
            assert(is_array($config));
            if (isset($config[TrailingSlashMiddleware::class])) {
                $config = $config[TrailingSlashMiddleware::class];
            }
        }

        $middleware = new TrailingSlashMiddleware();

        if ([] !== $config) {
            $middleware->setConfig($config);
        }

        return $middleware;
    }
}

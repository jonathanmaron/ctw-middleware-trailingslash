<?php
declare(strict_types=1);

namespace Ctw\Middleware\TrailingSlashMiddleware;

use Psr\Container\ContainerInterface;

class TrailingSlashMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): TrailingSlashMiddleware
    {
        return new TrailingSlashMiddleware();
    }
}

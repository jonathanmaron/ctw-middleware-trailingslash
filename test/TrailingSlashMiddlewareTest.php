<?php
declare(strict_types=1);

namespace CtwTest\Middleware\TrailingSlashMiddleware;

use Ctw\Http\HttpStatus;
use Laminas\ServiceManager\ServiceManager;
use Ctw\Middleware\TrailingSlashMiddleware\TrailingSlashMiddleware;
use Ctw\Middleware\TrailingSlashMiddleware\TrailingSlashMiddlewareFactory;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;

class TrailingSlashMiddlewareTest extends AbstractCase
{
    public function testTrailingSlashMiddleware(): void
    {
        $request  = Factory::createServerRequest('GET', '/path');
        $stack    = [
            $this->getInstance(),
        ];
        $response = Dispatcher::run($stack, $request);
        $this->assertEquals(HttpStatus::STATUS_MOVED_PERMANENTLY, $response->getStatusCode());
        $headers = $response->getHeaders();
        $this->assertTrue(isset($headers['Location'][0]));
        $this->assertEquals('/path/', $headers['Location'][0]);
    }

    private function getInstance(): TrailingSlashMiddleware
    {
        $container = new ServiceManager();
        $factory   = new TrailingSlashMiddlewareFactory();

        return $factory->__invoke($container);
    }
}

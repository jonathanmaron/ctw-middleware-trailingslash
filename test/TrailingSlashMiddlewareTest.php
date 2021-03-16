<?php
declare(strict_types=1);

namespace CtwTest\Middleware\TrailingSlashMiddleware;

use Ctw\Http\HttpStatus;
use Ctw\Middleware\TrailingSlashMiddleware\TrailingSlashMiddleware;
use Ctw\Middleware\TrailingSlashMiddleware\TrailingSlashMiddlewareFactory;
use Laminas\ServiceManager\ServiceManager;
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
        $this->assertArrayHasKey('Location', $headers,);
        $this->assertArrayHasKey(0, $headers['Location']);
        $this->assertEquals('/path/', $headers['Location'][0]);
    }

    public function testTrailingSlashMiddlewareWithNoPath(): void
    {
        $request  = Factory::createServerRequest('GET', '');
        $stack    = [
            $this->getInstance(),
        ];
        $response = Dispatcher::run($stack, $request);
        $this->assertEquals(HttpStatus::STATUS_MOVED_PERMANENTLY, $response->getStatusCode());
        $headers = $response->getHeaders();
        $this->assertArrayHasKey('Location', $headers,);
        $this->assertArrayHasKey(0, $headers['Location']);
        $this->assertEquals('/', $headers['Location'][0]);
    }

    public function testTrailingSlashMiddlewareWithTrailingSlash(): void
    {
        $request  = Factory::createServerRequest('GET', '/path/');
        $stack    = [
            $this->getInstance(),
        ];
        $response = Dispatcher::run($stack, $request);
        $this->assertEquals(HttpStatus::STATUS_OK, $response->getStatusCode());
    }

    private function getInstance(): TrailingSlashMiddleware
    {
        $container = new ServiceManager();
        $factory   = new TrailingSlashMiddlewareFactory();

        return $factory->__invoke($container);
    }
}

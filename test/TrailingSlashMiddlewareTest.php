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
        self::assertEquals(HttpStatus::STATUS_MOVED_PERMANENTLY, $response->getStatusCode());
        $headers = $response->getHeaders();
        self::assertArrayHasKey('Location', $headers,);
        self::assertArrayHasKey(0, $headers['Location']);
        self::assertEquals('/path/', $headers['Location'][0]);
    }

    public function testTrailingSlashMiddlewareWithNoPath(): void
    {
        $request  = Factory::createServerRequest('GET', '');
        $stack    = [
            $this->getInstance(),
        ];
        $response = Dispatcher::run($stack, $request);
        self::assertEquals(HttpStatus::STATUS_MOVED_PERMANENTLY, $response->getStatusCode());
        $headers = $response->getHeaders();
        self::assertArrayHasKey('Location', $headers,);
        self::assertArrayHasKey(0, $headers['Location']);
        self::assertEquals('/', $headers['Location'][0]);
    }

    public function testTrailingSlashMiddlewareWithTrailingSlash(): void
    {
        $request  = Factory::createServerRequest('GET', '/path/');
        $stack    = [
            $this->getInstance(),
        ];
        $response = Dispatcher::run($stack, $request);
        self::assertEquals(HttpStatus::STATUS_OK, $response->getStatusCode());
    }

    private function getInstance(): TrailingSlashMiddleware
    {
        $container = new ServiceManager();
        $factory   = new TrailingSlashMiddlewareFactory();

        return $factory->__invoke($container);
    }
}

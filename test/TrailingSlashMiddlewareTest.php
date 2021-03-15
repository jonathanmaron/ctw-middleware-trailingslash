<?php
declare(strict_types=1);

namespace CtwTest\Middleware\TrailingSlashMiddleware;

use Ctw\Http\HttpStatus;
use Ctw\Middleware\TrailingSlashMiddleware\TrailingSlashMiddleware;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;

class TrailingSlashMiddlewareTest extends AbstractCase
{
    public function testTrailingSlashMiddleware(): void
    {
        $serverParams = [];
        $request      = Factory::createServerRequest('GET', '/path', $serverParams);
        $stack        = [
            new TrailingSlashMiddleware(),
        ];
        $response     = Dispatcher::run($stack, $request);
        $this->assertEquals(HttpStatus::STATUS_MOVED_PERMANENTLY, $response->getStatusCode());
        $headers      = $response->getHeaders();
        $this->assertTrue(isset($headers['Location'][0]));
        $this->assertEquals('/path/', $headers['Location'][0]);
    }
}

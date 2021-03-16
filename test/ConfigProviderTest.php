<?php
declare(strict_types=1);

namespace CtwTest\Middleware\TrailingSlashMiddleware;

use Ctw\Middleware\TrailingSlashMiddleware\ConfigProvider;
use Ctw\Middleware\TrailingSlashMiddleware\TrailingSlashMiddleware;
use Ctw\Middleware\TrailingSlashMiddleware\TrailingSlashMiddlewareFactory;

class ConfigProviderTest extends AbstractCase
{
    public function testConfigProvider(): void
    {
        $configProvider = new ConfigProvider();

        $expected = [
            'dependencies' => [
                'factories' => [
                    TrailingSlashMiddleware::class => TrailingSlashMiddlewareFactory::class,
                ],
            ],
        ];

        $this->assertSame($expected, $configProvider->__invoke());
    }
}

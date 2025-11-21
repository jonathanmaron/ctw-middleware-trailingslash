<?php
declare(strict_types=1);

namespace CtwTest\Middleware\TrailingSlashMiddleware;

use Ctw\Middleware\TrailingSlashMiddleware\AbstractTrailingSlashMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AbstractTrailingSlashMiddlewareTest extends AbstractCase
{
    private AbstractTrailingSlashMiddleware $trailingSlashMiddleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->trailingSlashMiddleware = new class() extends AbstractTrailingSlashMiddleware {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request);
            }
        };
    }

    /**
     * Test that config is empty by default
     */
    public function testGetConfigReturnsEmptyArrayByDefault(): void
    {
        $actual = $this->trailingSlashMiddleware->getConfig();

        self::assertEmpty($actual);
    }

    /**
     * Test that setConfig stores configuration correctly
     */
    public function testSetConfigStoresConfigurationCorrectly(): void
    {
        $expected = [
            'key' => 'value',
        ];

        $this->trailingSlashMiddleware->setConfig($expected);
        $actual = $this->trailingSlashMiddleware->getConfig();

        self::assertSame($expected, $actual);
    }

    /**
     * Test that setConfig returns self for method chaining
     */
    public function testSetConfigReturnsInstanceForMethodChaining(): void
    {
        $config = [
            'test' => 'data',
        ];

        $result = $this->trailingSlashMiddleware->setConfig($config);

        self::assertSame($this->trailingSlashMiddleware, $result);
    }

    /**
     * Test that setConfig can be chained
     */
    public function testSetConfigCanBeChained(): void
    {
        $expected = [
            'chained' => true,
        ];

        $result = $this->trailingSlashMiddleware
            ->setConfig([
                'initial' => 'config',
            ])
            ->setConfig($expected);

        self::assertSame($this->trailingSlashMiddleware, $result);
        self::assertSame($expected, $this->trailingSlashMiddleware->getConfig());
    }

    /**
     * Test that setConfig overwrites previous configuration
     */
    public function testSetConfigOverwritesPreviousConfiguration(): void
    {
        $firstConfig = [
            'first' => 'value',
        ];
        $secondConfig = [
            'second' => 'value',
        ];

        $this->trailingSlashMiddleware->setConfig($firstConfig);
        $this->trailingSlashMiddleware->setConfig($secondConfig);

        $actual = $this->trailingSlashMiddleware->getConfig();

        self::assertSame($secondConfig, $actual);
        self::assertArrayNotHasKey('first', $actual);
    }

    /**
     * Test that empty array can be set as config
     */
    public function testSetConfigWithEmptyArray(): void
    {
        $this->trailingSlashMiddleware->setConfig([
            'initial' => 'data',
        ]);
        $this->trailingSlashMiddleware->setConfig([]);

        $actual = $this->trailingSlashMiddleware->getConfig();

        self::assertEmpty($actual);
    }

    /**
     * Test that complex nested arrays are stored correctly
     */
    public function testSetConfigWithComplexNestedArrays(): void
    {
        $expected = [
            'path_disable' => ['/admin', '/api'],
            'nested' => [
                'level1' => [
                    'level2' => 'value',
                ],
            ],
        ];

        $this->trailingSlashMiddleware->setConfig($expected);
        $actual = $this->trailingSlashMiddleware->getConfig();

        self::assertSame($expected, $actual);
    }

    /**
     * Test that numeric array keys are preserved
     */
    public function testSetConfigPreservesNumericArrayKeys(): void
    {
        $expected = [
            0 => 'first',
            1 => 'second',
            10 => 'tenth',
        ];

        $this->trailingSlashMiddleware->setConfig($expected);
        $actual = $this->trailingSlashMiddleware->getConfig();

        self::assertSame($expected, $actual);
    }

    /**
     * Test that string keys are preserved
     */
    public function testSetConfigPreservesStringKeys(): void
    {
        $expected = [
            'string_key' => 'value1',
            'another-key' => 'value2',
            'key.with.dots' => 'value3',
        ];

        $this->trailingSlashMiddleware->setConfig($expected);
        $actual = $this->trailingSlashMiddleware->getConfig();

        self::assertSame($expected, $actual);
    }
}

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
     * Test that getConfig returns an empty array when no configuration has been set.
     */
    public function testGetConfigReturnsEmptyArrayByDefault(): void
    {
        $actual = $this->trailingSlashMiddleware->getConfig();

        self::assertEmpty($actual);
    }

    /**
     * Test that getConfig returns the stored array when setConfig has been called with a populated configuration.
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
     * Test that setConfig returns the same middleware instance when called, enabling method chaining.
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
     * Test that setConfig applies the final configuration when invoked twice in a fluent chain.
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
     * Test that setConfig replaces the prior configuration when called a second time.
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
     * Test that setConfig resets the configuration when called with an empty array after a populated one.
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
     * Test that setConfig preserves the structure when given a complex nested configuration array.
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
     * Test that setConfig preserves the keys when the configuration array uses numeric keys.
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
     * Test that setConfig preserves the keys when the configuration array uses string keys.
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

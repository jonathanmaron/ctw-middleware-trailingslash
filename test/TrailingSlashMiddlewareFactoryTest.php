<?php
declare(strict_types=1);

namespace CtwTest\Middleware\TrailingSlashMiddleware;

use Ctw\Middleware\TrailingSlashMiddleware\TrailingSlashMiddleware;
use Ctw\Middleware\TrailingSlashMiddleware\TrailingSlashMiddlewareFactory;
use Psr\Container\ContainerInterface;

final class TrailingSlashMiddlewareFactoryTest extends AbstractCase
{
    private TrailingSlashMiddlewareFactory $trailingSlashMiddlewareFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->trailingSlashMiddlewareFactory = new TrailingSlashMiddlewareFactory();
    }

    /**
     * Test that factory creates TrailingSlashMiddleware instance
     */
    public function testInvokeCreatesTrailingSlashMiddlewareInstance(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')
            ->willReturn(false);

        $actual = ($this->trailingSlashMiddlewareFactory)($container);

        // @phpstan-ignore-next-line
        self::assertInstanceOf(TrailingSlashMiddleware::class, $actual);
    }

    /**
     * Test that factory creates middleware without config when container has no config
     */
    public function testInvokeCreatesMiddlewareWithoutConfigWhenContainerHasNoConfig(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())
            ->method('has')
            ->with('config')
            ->willReturn(false);

        $middleware = ($this->trailingSlashMiddlewareFactory)($container);

        // @phpstan-ignore-next-line
        self::assertInstanceOf(TrailingSlashMiddleware::class, $middleware);
        self::assertEmpty($middleware->getConfig());
    }

    /**
     * Test that factory creates middleware with empty config when container config is empty
     */
    public function testInvokeCreatesMiddlewareWithEmptyConfigWhenContainerConfigIsEmpty(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')
            ->with('config')
            ->willReturn(true);
        $container->method('get')
            ->with('config')
            ->willReturn([]);

        $middleware = ($this->trailingSlashMiddlewareFactory)($container);

        // @phpstan-ignore-next-line
        self::assertInstanceOf(TrailingSlashMiddleware::class, $middleware);
        self::assertEmpty($middleware->getConfig());
    }

    /**
     * Test that factory creates middleware without applying config when no middleware-specific config exists
     */
    public function testInvokeCreatesMiddlewareWithoutApplyingConfigWhenNoMiddlewareSpecificConfig(): void
    {
        $containerConfig = [
            'other_service' => [
                'key' => 'value',
            ],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')
            ->with('config')
            ->willReturn(true);
        $container->method('get')
            ->with('config')
            ->willReturn($containerConfig);

        $middleware = ($this->trailingSlashMiddlewareFactory)($container);

        // @phpstan-ignore-next-line
        self::assertInstanceOf(TrailingSlashMiddleware::class, $middleware);
        // The factory sets the full container config, not the middleware-specific one
        self::assertSame($containerConfig, $middleware->getConfig());
    }

    /**
     * Test that factory applies middleware-specific configuration when present
     */
    public function testInvokeAppliesMiddlewareSpecificConfiguration(): void
    {
        $middlewareConfig = [
            'path_disable' => ['/admin', '/api'],
        ];
        $containerConfig = [
            TrailingSlashMiddleware::class => $middlewareConfig,
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')
            ->with('config')
            ->willReturn(true);
        $container->method('get')
            ->with('config')
            ->willReturn($containerConfig);

        $middleware = ($this->trailingSlashMiddlewareFactory)($container);

        // @phpstan-ignore-next-line
        self::assertInstanceOf(TrailingSlashMiddleware::class, $middleware);
        self::assertSame($middlewareConfig, $middleware->getConfig());
    }

    /**
     * Test that factory applies nested middleware configuration correctly
     */
    public function testInvokeAppliesNestedMiddlewareConfiguration(): void
    {
        $middlewareConfig = [
            'path_disable' => ['/admin', '/api', '/health'],
            'custom_option' => true,
            'nested' => [
                'key' => 'value',
            ],
        ];
        $containerConfig = [
            'other_config' => [
                'something' => 'else',
            ],
            TrailingSlashMiddleware::class => $middlewareConfig,
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')
            ->with('config')
            ->willReturn(true);
        $container->method('get')
            ->with('config')
            ->willReturn($containerConfig);

        $middleware = ($this->trailingSlashMiddlewareFactory)($container);

        self::assertSame($middlewareConfig, $middleware->getConfig());
    }

    /**
     * Test that factory only calls container has once
     */
    public function testInvokeCallsContainerHasOnce(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())
            ->method('has')
            ->with('config')
            ->willReturn(false);

        ($this->trailingSlashMiddlewareFactory)($container);
    }

    /**
     * Test that factory calls container get when config exists
     */
    public function testInvokeCallsContainerGetWhenConfigExists(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')
            ->with('config')
            ->willReturn(true);
        $container->expects(self::once())
            ->method('get')
            ->with('config')
            ->willReturn([]);

        ($this->trailingSlashMiddlewareFactory)($container);
    }

    /**
     * Test that factory does not call container get when config does not exist
     */
    public function testInvokeDoesNotCallContainerGetWhenConfigDoesNotExist(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')
            ->with('config')
            ->willReturn(false);
        $container->expects(self::never())
            ->method('get');

        ($this->trailingSlashMiddlewareFactory)($container);
    }

    /**
     * Test that factory creates unique instances on multiple invocations
     */
    public function testInvokeCreatesUniqueInstancesOnMultipleInvocations(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')
            ->willReturn(false);

        $firstInstance = ($this->trailingSlashMiddlewareFactory)($container);
        $secondInstance = ($this->trailingSlashMiddlewareFactory)($container);

        self::assertNotSame($firstInstance, $secondInstance);
    }

    /**
     * Test that factory handles config with empty middleware-specific array
     */
    public function testInvokeHandlesConfigWithEmptyMiddlewareSpecificArray(): void
    {
        $containerConfig = [
            TrailingSlashMiddleware::class => [],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')
            ->with('config')
            ->willReturn(true);
        $container->method('get')
            ->with('config')
            ->willReturn($containerConfig);

        $middleware = ($this->trailingSlashMiddlewareFactory)($container);

        // @phpstan-ignore-next-line
        self::assertInstanceOf(TrailingSlashMiddleware::class, $middleware);
        self::assertEmpty($middleware->getConfig());
    }
}

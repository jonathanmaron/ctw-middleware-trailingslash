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
     * Test that __invoke returns a TrailingSlashMiddleware instance when the container has no config service.
     */
    public function testInvokeCreatesTrailingSlashMiddlewareInstance(): void
    {
        $container = self::createStub(ContainerInterface::class);
        $container->method('has')
            ->willReturn(false);

        $actual = ($this->trailingSlashMiddlewareFactory)($container);

        // @phpstan-ignore-next-line
        self::assertInstanceOf(TrailingSlashMiddleware::class, $actual);
    }

    /**
     * Test that __invoke leaves the middleware config empty when the container reports no config service.
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
     * Test that __invoke leaves the middleware config empty when the container config service is an empty array.
     */
    public function testInvokeCreatesMiddlewareWithEmptyConfigWhenContainerConfigIsEmpty(): void
    {
        $container = self::createStub(ContainerInterface::class);
        $container->method('has')
            ->willReturn(true);
        $container->method('get')
            ->willReturn([]);

        $middleware = ($this->trailingSlashMiddlewareFactory)($container);

        // @phpstan-ignore-next-line
        self::assertInstanceOf(TrailingSlashMiddleware::class, $middleware);
        self::assertEmpty($middleware->getConfig());
    }

    /**
     * Test that __invoke applies the full container config when it contains no middleware-specific entry.
     */
    public function testInvokeCreatesMiddlewareWithoutApplyingConfigWhenNoMiddlewareSpecificConfig(): void
    {
        $containerConfig = [
            'other_service' => [
                'key' => 'value',
            ],
        ];

        $container = self::createStub(ContainerInterface::class);
        $container->method('has')
            ->willReturn(true);
        $container->method('get')
            ->willReturn($containerConfig);

        $middleware = ($this->trailingSlashMiddlewareFactory)($container);

        // @phpstan-ignore-next-line
        self::assertInstanceOf(TrailingSlashMiddleware::class, $middleware);
        // The factory sets the full container config, not the middleware-specific one
        self::assertSame($containerConfig, $middleware->getConfig());
    }

    /**
     * Test that __invoke applies the middleware-specific configuration when the container config nests it by class name.
     */
    public function testInvokeAppliesMiddlewareSpecificConfiguration(): void
    {
        $middlewareConfig = [
            'path_disable' => ['/admin', '/api'],
        ];
        $containerConfig = [
            TrailingSlashMiddleware::class => $middlewareConfig,
        ];

        $container = self::createStub(ContainerInterface::class);
        $container->method('has')
            ->willReturn(true);
        $container->method('get')
            ->willReturn($containerConfig);

        $middleware = ($this->trailingSlashMiddlewareFactory)($container);

        // @phpstan-ignore-next-line
        self::assertInstanceOf(TrailingSlashMiddleware::class, $middleware);
        self::assertSame($middlewareConfig, $middleware->getConfig());
    }

    /**
     * Test that __invoke extracts only the nested middleware entry when the container config also holds other keys.
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

        $container = self::createStub(ContainerInterface::class);
        $container->method('has')
            ->willReturn(true);
        $container->method('get')
            ->willReturn($containerConfig);

        $middleware = ($this->trailingSlashMiddlewareFactory)($container);

        self::assertSame($middlewareConfig, $middleware->getConfig());
    }

    /**
     * Test that __invoke queries the container for the config service exactly once when building the middleware.
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
     * Test that __invoke retrieves the config service exactly once when the container reports it exists.
     */
    public function testInvokeCallsContainerGetWhenConfigExists(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')
            ->willReturn(true);
        $container->expects(self::once())
            ->method('get')
            ->with('config')
            ->willReturn([]);

        ($this->trailingSlashMiddlewareFactory)($container);
    }

    /**
     * Test that __invoke never retrieves the config service when the container reports it is absent.
     */
    public function testInvokeDoesNotCallContainerGetWhenConfigDoesNotExist(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')
            ->willReturn(false);
        $container->expects(self::never())
            ->method('get');

        ($this->trailingSlashMiddlewareFactory)($container);
    }

    /**
     * Test that __invoke returns a distinct middleware instance each time it is called with the same container.
     */
    public function testInvokeCreatesUniqueInstancesOnMultipleInvocations(): void
    {
        $container = self::createStub(ContainerInterface::class);
        $container->method('has')
            ->willReturn(false);

        $firstInstance = ($this->trailingSlashMiddlewareFactory)($container);
        $secondInstance = ($this->trailingSlashMiddlewareFactory)($container);

        self::assertNotSame($firstInstance, $secondInstance);
    }

    /**
     * Test that __invoke leaves the middleware config empty when the middleware-specific entry is an empty array.
     */
    public function testInvokeHandlesConfigWithEmptyMiddlewareSpecificArray(): void
    {
        $containerConfig = [
            TrailingSlashMiddleware::class => [],
        ];

        $container = self::createStub(ContainerInterface::class);
        $container->method('has')
            ->willReturn(true);
        $container->method('get')
            ->willReturn($containerConfig);

        $middleware = ($this->trailingSlashMiddlewareFactory)($container);

        // @phpstan-ignore-next-line
        self::assertInstanceOf(TrailingSlashMiddleware::class, $middleware);
        self::assertEmpty($middleware->getConfig());
    }
}

<?php
declare(strict_types=1);

namespace CtwTest\Middleware\TrailingSlashMiddleware;

use Ctw\Middleware\TrailingSlashMiddleware\ConfigProvider;
use Ctw\Middleware\TrailingSlashMiddleware\TrailingSlashMiddleware;
use Ctw\Middleware\TrailingSlashMiddleware\TrailingSlashMiddlewareFactory;

final class ConfigProviderTest extends AbstractCase
{
    private ConfigProvider $configProvider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configProvider = new ConfigProvider();
    }

    /**
     * Test that invoke returns complete configuration array
     */
    public function testInvokeReturnsCompleteConfiguration(): void
    {
        $expected = [
            'dependencies' => [
                'factories' => [
                    TrailingSlashMiddleware::class => TrailingSlashMiddlewareFactory::class,
                ],
            ],
        ];

        $actual = ($this->configProvider)();

        self::assertSame($expected, $actual);
    }

    /**
     * Test that invoke returns array with dependencies key
     */
    public function testInvokeReturnsArrayWithDependenciesKey(): void
    {
        $actual = ($this->configProvider)();

        self::assertArrayHasKey('dependencies', $actual);
        self::assertIsArray($actual['dependencies']);
    }

    /**
     * Test that dependencies contain factories configuration
     */
    public function testGetDependenciesReturnsFactoriesConfiguration(): void
    {
        $expected = [
            'factories' => [
                TrailingSlashMiddleware::class => TrailingSlashMiddlewareFactory::class,
            ],
        ];

        $actual = $this->configProvider->getDependencies();

        self::assertSame($expected, $actual);
    }

    /**
     * Test that dependencies contain factories key
     */
    public function testGetDependenciesContainsFactoriesKey(): void
    {
        $actual = $this->configProvider->getDependencies();

        self::assertArrayHasKey('factories', $actual);
        self::assertIsArray($actual['factories']);
    }

    /**
     * Test that factory mapping is correct for TrailingSlashMiddleware
     */
    public function testFactoryMappingIsCorrect(): void
    {
        $dependencies = $this->configProvider->getDependencies();
        self::assertArrayHasKey('factories', $dependencies);

        $factories = $dependencies['factories'];
        self::assertIsArray($factories);

        self::assertArrayHasKey(TrailingSlashMiddleware::class, $factories);
        self::assertSame(TrailingSlashMiddlewareFactory::class, $factories[TrailingSlashMiddleware::class]);
    }

    /**
     * Test that invoke and getDependencies return consistent results
     */
    public function testInvokeAndGetDependenciesAreConsistent(): void
    {
        $invokeResult = ($this->configProvider)();
        $dependenciesResult = $this->configProvider->getDependencies();

        self::assertSame($dependenciesResult, $invokeResult['dependencies']);
    }

    /**
     * Test that multiple invocations return identical results
     */
    public function testMultipleInvocationsReturnIdenticalResults(): void
    {
        $firstResult = ($this->configProvider)();
        $secondResult = ($this->configProvider)();

        self::assertSame($firstResult, $secondResult);
    }
}

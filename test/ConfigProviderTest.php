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
     * Test that __invoke returns the complete dependencies configuration when the provider is called.
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
     * Test that __invoke returns an array containing an array-typed dependencies key when the provider is called.
     */
    public function testInvokeReturnsArrayWithDependenciesKey(): void
    {
        $actual = ($this->configProvider)();

        self::assertArrayHasKey('dependencies', $actual);
        self::assertIsArray($actual['dependencies']);
    }

    /**
     * Test that getDependencies returns the factories mapping when the provider exposes its dependencies.
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
     * Test that getDependencies returns an array containing an array-typed factories key.
     */
    public function testGetDependenciesContainsFactoriesKey(): void
    {
        $actual = $this->configProvider->getDependencies();

        self::assertArrayHasKey('factories', $actual);
        self::assertIsArray($actual['factories']);
    }

    /**
     * Test that getDependencies maps TrailingSlashMiddleware to its factory when resolving the factories entry.
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
     * Test that __invoke nests the getDependencies result under its dependencies key when both are compared.
     */
    public function testInvokeAndGetDependenciesAreConsistent(): void
    {
        $invokeResult = ($this->configProvider)();
        $dependenciesResult = $this->configProvider->getDependencies();

        self::assertSame($dependenciesResult, $invokeResult['dependencies']);
    }

    /**
     * Test that __invoke returns identical configuration when the provider is called more than once.
     */
    public function testMultipleInvocationsReturnIdenticalResults(): void
    {
        $firstResult = ($this->configProvider)();
        $secondResult = ($this->configProvider)();

        self::assertSame($firstResult, $secondResult);
    }
}

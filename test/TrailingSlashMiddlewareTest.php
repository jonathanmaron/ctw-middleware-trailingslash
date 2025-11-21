<?php
declare(strict_types=1);

namespace CtwTest\Middleware\TrailingSlashMiddleware;

use Ctw\Http\HttpStatus;
use Ctw\Middleware\TrailingSlashMiddleware\TrailingSlashMiddleware;
use Ctw\Middleware\TrailingSlashMiddleware\TrailingSlashMiddlewareFactory;
use Laminas\ServiceManager\ServiceManager;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use Psr\Container\ContainerInterface;

final class TrailingSlashMiddlewareTest extends AbstractCase
{
    /**
     * Test that path without trailing slash redirects with 301
     */
    public function testProcessRedirectsPathWithoutTrailingSlash(): void
    {
        $request = Factory::createServerRequest('GET', '/path');
        $stack = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_MOVED_PERMANENTLY, $response->getStatusCode());

        $headers = $response->getHeaders();
        self::assertArrayHasKey('Location', $headers);
        self::assertArrayHasKey(0, $headers['Location']);
        self::assertSame('/path/', $headers['Location'][0]);
    }

    /**
     * Test that empty path redirects to root with trailing slash
     */
    public function testProcessRedirectsEmptyPathToRoot(): void
    {
        $request = Factory::createServerRequest('GET', '');
        $stack = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_MOVED_PERMANENTLY, $response->getStatusCode());

        $headers = $response->getHeaders();
        self::assertArrayHasKey('Location', $headers);
        self::assertArrayHasKey(0, $headers['Location']);
        self::assertSame('/', $headers['Location'][0]);
    }

    /**
     * Test that path with trailing slash passes through without redirect
     */
    public function testProcessPassesThroughPathWithTrailingSlash(): void
    {
        $request = Factory::createServerRequest('GET', '/path/');
        $stack = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_OK, $response->getStatusCode());
    }

    /**
     * Test that root path passes through without redirect
     */
    public function testProcessPassesThroughRootPath(): void
    {
        $request = Factory::createServerRequest('GET', '/');
        $stack = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_OK, $response->getStatusCode());
    }

    /**
     * Test that path with file extension does not get trailing slash
     */
    public function testProcessDoesNotAddTrailingSlashToPathWithFileExtension(): void
    {
        $request = Factory::createServerRequest('GET', '/file.txt');
        $stack = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_OK, $response->getStatusCode());
    }

    /**
     * Test that path with PHP extension does not get trailing slash
     */
    public function testProcessDoesNotAddTrailingSlashToPhpFile(): void
    {
        $request = Factory::createServerRequest('GET', '/index.php');
        $stack = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_OK, $response->getStatusCode());
    }

    /**
     * Test that path with HTML extension does not get trailing slash
     */
    public function testProcessDoesNotAddTrailingSlashToHtmlFile(): void
    {
        $request = Factory::createServerRequest('GET', '/page.html');
        $stack = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_OK, $response->getStatusCode());
    }

    /**
     * Test that path with JSON extension does not get trailing slash
     */
    public function testProcessDoesNotAddTrailingSlashToJsonFile(): void
    {
        $request = Factory::createServerRequest('GET', '/api/data.json');
        $stack = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_OK, $response->getStatusCode());
    }

    /**
     * Test that deep nested path without trailing slash redirects
     */
    public function testProcessRedirectsDeepNestedPath(): void
    {
        $request = Factory::createServerRequest('GET', '/path/to/nested/resource');
        $stack = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_MOVED_PERMANENTLY, $response->getStatusCode());

        $headers = $response->getHeaders();
        self::assertSame('/path/to/nested/resource/', $headers['Location'][0]);
    }

    /**
     * Test that path with query string preserves query string in redirect
     */
    public function testProcessPreservesQueryStringInRedirect(): void
    {
        $request = Factory::createServerRequest('GET', '/path?foo=bar');
        $stack = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_MOVED_PERMANENTLY, $response->getStatusCode());

        $headers = $response->getHeaders();
        self::assertSame('/path/?foo=bar', $headers['Location'][0]);
    }

    /**
     * Test that path with fragment preserves fragment in redirect
     */
    public function testProcessPreservesFragmentInRedirect(): void
    {
        $request = Factory::createServerRequest('GET', '/path#section');
        $stack = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_MOVED_PERMANENTLY, $response->getStatusCode());

        $headers = $response->getHeaders();
        self::assertSame('/path/#section', $headers['Location'][0]);
    }

    /**
     * Test that path with query string and fragment preserves both in redirect
     */
    public function testProcessPreservesQueryStringAndFragmentInRedirect(): void
    {
        $request = Factory::createServerRequest('GET', '/path?foo=bar#section');
        $stack = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_MOVED_PERMANENTLY, $response->getStatusCode());

        $headers = $response->getHeaders();
        self::assertSame('/path/?foo=bar#section', $headers['Location'][0]);
    }

    /**
     * Test that disabled paths are not redirected
     */
    public function testProcessSkipsDisabledPaths(): void
    {
        $config = [
            TrailingSlashMiddleware::class => [
                'path_disable' => ['/admin', '/api'],
            ],
        ];

        $request = Factory::createServerRequest('GET', '/admin/users');
        $stack = [$this->getInstanceWithConfig($config)];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_OK, $response->getStatusCode());
    }

    /**
     * Test that second disabled path is skipped
     */
    public function testProcessSkipsSecondDisabledPath(): void
    {
        $config = [
            TrailingSlashMiddleware::class => [
                'path_disable' => ['/admin', '/api'],
            ],
        ];

        $request = Factory::createServerRequest('GET', '/api/endpoint');
        $stack = [$this->getInstanceWithConfig($config)];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_OK, $response->getStatusCode());
    }

    /**
     * Test that paths not in disabled list are still redirected
     */
    public function testProcessRedirectsPathsNotInDisabledList(): void
    {
        $config = [
            TrailingSlashMiddleware::class => [
                'path_disable' => ['/admin', '/api'],
            ],
        ];

        $request = Factory::createServerRequest('GET', '/public/page');
        $stack = [$this->getInstanceWithConfig($config)];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_MOVED_PERMANENTLY, $response->getStatusCode());

        $headers = $response->getHeaders();
        self::assertSame('/public/page/', $headers['Location'][0]);
    }

    /**
     * Test that exact match of disabled path is skipped
     */
    public function testProcessSkipsExactMatchOfDisabledPath(): void
    {
        $config = [
            TrailingSlashMiddleware::class => [
                'path_disable' => ['/health'],
            ],
        ];

        $request = Factory::createServerRequest('GET', '/health');
        $stack = [$this->getInstanceWithConfig($config)];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_OK, $response->getStatusCode());
    }

    /**
     * Test that path starting with disabled prefix is skipped
     */
    public function testProcessSkipsPathStartingWithDisabledPrefix(): void
    {
        $config = [
            TrailingSlashMiddleware::class => [
                'path_disable' => ['/api/v1'],
            ],
        ];

        $request = Factory::createServerRequest('GET', '/api/v1/users');
        $stack = [$this->getInstanceWithConfig($config)];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_OK, $response->getStatusCode());
    }

    /**
     * Test that similar but non-matching path is not skipped
     */
    public function testProcessDoesNotSkipSimilarButNonMatchingPath(): void
    {
        $config = [
            TrailingSlashMiddleware::class => [
                'path_disable' => ['/api'],
            ],
        ];

        $request = Factory::createServerRequest('GET', '/application');
        $stack = [$this->getInstanceWithConfig($config)];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_MOVED_PERMANENTLY, $response->getStatusCode());
    }

    /**
     * Test that path with special characters redirects correctly
     */
    public function testProcessRedirectsPathWithSpecialCharacters(): void
    {
        $request = Factory::createServerRequest('GET', '/path-with-dashes_and_underscores');
        $stack = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_MOVED_PERMANENTLY, $response->getStatusCode());

        $headers = $response->getHeaders();
        self::assertSame('/path-with-dashes_and_underscores/', $headers['Location'][0]);
    }

    /**
     * Test that path with encoded characters redirects correctly
     */
    public function testProcessRedirectsPathWithEncodedCharacters(): void
    {
        $request = Factory::createServerRequest('GET', '/path%20with%20spaces');
        $stack = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_MOVED_PERMANENTLY, $response->getStatusCode());

        $headers = $response->getHeaders();
        self::assertSame('/path%20with%20spaces/', $headers['Location'][0]);
    }

    /**
     * Test that path with multiple query parameters preserves all parameters
     */
    public function testProcessPreservesMultipleQueryParameters(): void
    {
        $request = Factory::createServerRequest('GET', '/path?foo=bar&baz=qux&test=value');
        $stack = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_MOVED_PERMANENTLY, $response->getStatusCode());

        $headers = $response->getHeaders();
        self::assertSame('/path/?foo=bar&baz=qux&test=value', $headers['Location'][0]);
    }

    /**
     * Test that single character path redirects correctly
     */
    public function testProcessRedirectsSingleCharacterPath(): void
    {
        $request = Factory::createServerRequest('GET', '/a');
        $stack = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_MOVED_PERMANENTLY, $response->getStatusCode());

        $headers = $response->getHeaders();
        self::assertSame('/a/', $headers['Location'][0]);
    }

    /**
     * Test that path with dots is treated as file with extension
     */
    public function testProcessDoesNotAddTrailingSlashToPathWithDots(): void
    {
        $request = Factory::createServerRequest('GET', '/path.with.dots');
        $stack = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        // pathinfo() treats the last part after the dot as an extension
        self::assertSame(HttpStatus::STATUS_OK, $response->getStatusCode());
    }

    /**
     * Test that middleware works with different HTTP methods
     */
    public function testProcessWorksWithPostMethod(): void
    {
        $request = Factory::createServerRequest('POST', '/submit');
        $stack = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_MOVED_PERMANENTLY, $response->getStatusCode());

        $headers = $response->getHeaders();
        self::assertSame('/submit/', $headers['Location'][0]);
    }

    /**
     * Test that file with multiple dots preserves extension check
     */
    public function testProcessDoesNotAddTrailingSlashToFileWithMultipleDots(): void
    {
        $request = Factory::createServerRequest('GET', '/archive.tar.gz');
        $stack = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_OK, $response->getStatusCode());
    }

    /**
     * Test that path ending with trailing slash and having extension passes through
     */
    public function testProcessPassesThroughPathWithExtensionAndTrailingSlash(): void
    {
        $request = Factory::createServerRequest('GET', '/file.txt/');
        $stack = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_OK, $response->getStatusCode());
    }

    private function getInstance(): TrailingSlashMiddleware
    {
        $container = new ServiceManager();
        $factory = new TrailingSlashMiddlewareFactory();

        return $factory->__invoke($container);
    }

    private function getInstanceWithConfig(array $config): TrailingSlashMiddleware
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')
            ->with('config')
            ->willReturn(true);
        $container->method('get')
            ->with('config')
            ->willReturn($config);

        $factory = new TrailingSlashMiddlewareFactory();

        return $factory->__invoke($container);
    }
}

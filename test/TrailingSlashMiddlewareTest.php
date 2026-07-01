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
     * Test that process returns a 301 redirect to the slashed path when the request path has no trailing slash.
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
     * Test that process returns a 301 redirect to root when the request path is an empty string.
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
     * Test that process delegates to the handler without redirecting when the request path already ends in a slash.
     */
    public function testProcessPassesThroughPathWithTrailingSlash(): void
    {
        $request = Factory::createServerRequest('GET', '/path/');
        $stack = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_OK, $response->getStatusCode());
    }

    /**
     * Test that process delegates to the handler without redirecting when the request path is the root slash.
     */
    public function testProcessPassesThroughRootPath(): void
    {
        $request = Factory::createServerRequest('GET', '/');
        $stack = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_OK, $response->getStatusCode());
    }

    /**
     * Test that process delegates to the handler without redirecting when the request path has a file extension.
     */
    public function testProcessDoesNotAddTrailingSlashToPathWithFileExtension(): void
    {
        $request = Factory::createServerRequest('GET', '/file.txt');
        $stack = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_OK, $response->getStatusCode());
    }

    /**
     * Test that process delegates to the handler without redirecting when the request path is a .php file.
     */
    public function testProcessDoesNotAddTrailingSlashToPhpFile(): void
    {
        $request = Factory::createServerRequest('GET', '/index.php');
        $stack = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_OK, $response->getStatusCode());
    }

    /**
     * Test that process delegates to the handler without redirecting when the request path is a .html file.
     */
    public function testProcessDoesNotAddTrailingSlashToHtmlFile(): void
    {
        $request = Factory::createServerRequest('GET', '/page.html');
        $stack = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_OK, $response->getStatusCode());
    }

    /**
     * Test that process delegates to the handler without redirecting when a nested request path is a .json file.
     */
    public function testProcessDoesNotAddTrailingSlashToJsonFile(): void
    {
        $request = Factory::createServerRequest('GET', '/api/data.json');
        $stack = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_OK, $response->getStatusCode());
    }

    /**
     * Test that process returns a 301 redirect to the slashed path when a deeply nested path lacks a trailing slash.
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
     * Test that process preserves the query string in the Location header when redirecting an unslashed path.
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
     * Test that process preserves the fragment in the Location header when redirecting an unslashed path.
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
     * Test that process preserves both query string and fragment in the Location header when redirecting.
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
     * Test that process delegates to the handler without redirecting when the path matches the first disabled prefix.
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
     * Test that process delegates to the handler without redirecting when the path matches a later disabled prefix.
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
     * Test that process still returns a 301 redirect when the path matches none of the disabled prefixes.
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
     * Test that process delegates to the handler without redirecting when the path exactly equals a disabled prefix.
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
     * Test that process delegates to the handler without redirecting when the path starts with a multi-segment disabled prefix.
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
     * Test that process still returns a 301 redirect when the path merely resembles but does not start with a disabled prefix.
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
     * Test that process returns a 301 redirect preserving dashes and underscores when the unslashed path contains them.
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
     * Test that process returns a 301 redirect preserving percent-encoding when the unslashed path is URL-encoded.
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
     * Test that process preserves every query parameter in the Location header when redirecting a multi-parameter path.
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
     * Test that process returns a 301 redirect to the slashed path when the request path is a single character segment.
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
     * Test that process delegates to the handler without redirecting when a dotted path is read as having an extension.
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
     * Test that process returns a 301 redirect to the slashed path when an unslashed path is requested via POST.
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
     * Test that process delegates to the handler without redirecting when the path is a file with multiple dots.
     */
    public function testProcessDoesNotAddTrailingSlashToFileWithMultipleDots(): void
    {
        $request = Factory::createServerRequest('GET', '/archive.tar.gz');
        $stack = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_OK, $response->getStatusCode());
    }

    /**
     * Test that process delegates to the handler without redirecting when the path has an extension and already ends in a slash.
     */
    public function testProcessPassesThroughPathWithExtensionAndTrailingSlash(): void
    {
        $request = Factory::createServerRequest('GET', '/file.txt/');
        $stack = [$this->getInstance()];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_OK, $response->getStatusCode());
    }

    /**
     * Test that process ignores the disable list and returns a 301 redirect when path_disable is not an array.
     */
    public function testProcessRedirectsWhenPathDisableIsNotAnArray(): void
    {
        $config = [
            TrailingSlashMiddleware::class => [
                'path_disable' => '/admin',
            ],
        ];

        $request = Factory::createServerRequest('GET', '/admin/users');
        $stack = [$this->getInstanceWithConfig($config)];
        $response = Dispatcher::run($stack, $request);

        self::assertSame(HttpStatus::STATUS_MOVED_PERMANENTLY, $response->getStatusCode());

        $headers = $response->getHeaders();
        self::assertSame('/admin/users/', $headers['Location'][0]);
    }

    /**
     * Test that process skips non-string disable entries and still matches a later string prefix in the disable list.
     */
    public function testProcessSkipsNonStringEntriesInDisableList(): void
    {
        $config = [
            TrailingSlashMiddleware::class => [
                'path_disable' => [123, '/admin'],
            ],
        ];

        $request = Factory::createServerRequest('GET', '/admin/users');
        $stack = [$this->getInstanceWithConfig($config)];
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
        $container = self::createStub(ContainerInterface::class);
        $container->method('has')
            ->willReturn(true);
        $container->method('get')
            ->willReturn($config);

        $factory = new TrailingSlashMiddlewareFactory();

        return $factory->__invoke($container);
    }
}

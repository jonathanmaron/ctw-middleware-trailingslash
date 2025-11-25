# Package "ctw/ctw-middleware-trailingslash"

[![Latest Stable Version](https://poser.pugx.org/ctw/ctw-middleware-trailingslash/v/stable)](https://packagist.org/packages/ctw/ctw-middleware-trailingslash)
[![GitHub Actions](https://github.com/jonathanmaron/ctw-middleware-trailingslash/actions/workflows/tests.yml/badge.svg)](https://github.com/jonathanmaron/ctw-middleware-trailingslash/actions/workflows/tests.yml)
[![Scrutinizer Build](https://scrutinizer-ci.com/g/jonathanmaron/ctw-middleware-trailingslash/badges/build.png?b=master)](https://scrutinizer-ci.com/g/jonathanmaron/ctw-middleware-trailingslash/build-status/master)
[![Scrutinizer Quality](https://scrutinizer-ci.com/g/jonathanmaron/ctw-middleware-trailingslash/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jonathanmaron/ctw-middleware-trailingslash/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/jonathanmaron/ctw-middleware-trailingslash/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/jonathanmaron/ctw-middleware-trailingslash/?branch=master)

PSR-15 middleware that enforces trailing slashes on URLs by redirecting with HTTP 301, ensuring consistent URL canonicalization across your application.

## Introduction

### Why This Library Exists

URLs with and without trailing slashes (e.g., `/about` vs `/about/`) are technically different resources. Without proper handling, the same content may be accessible at multiple URLs, causing SEO problems and potential caching issues.

This middleware enforces a consistent trailing slash convention by:

- **Automatic redirects**: Adds trailing slashes to URLs missing them
- **Permanent redirects**: Uses HTTP 301 to preserve SEO value
- **File exclusion**: Skips URLs with file extensions (e.g., `/style.css`)
- **Path configuration**: Exclude specific paths from processing (e.g., `/api/`)
- **Query preservation**: Maintains query strings during redirect

### Problems This Library Solves

1. **Duplicate content**: Search engines index both `/page` and `/page/` as separate pages
2. **Inconsistent linking**: Internal links may mix both formats
3. **Cache fragmentation**: CDNs may cache the same page under multiple URLs
4. **Relative URL issues**: Browser relative URL resolution differs based on trailing slash
5. **Manual redirects**: Maintaining nginx/Apache redirect rules is error-prone

### Where to Use This Library

- **SEO-focused websites**: Ensure canonical URLs for all pages
- **Content management systems**: Enforce URL consistency across dynamic content
- **Marketing sites**: Prevent duplicate content penalties
- **Multi-author platforms**: Normalize URLs regardless of how they're entered
- **API gateways**: Exclude API paths while normalizing web paths

### Design Goals

1. **Permanent redirects**: Uses HTTP 301 for SEO benefit
2. **Smart file detection**: Skips URLs with file extensions automatically
3. **Configurable exclusions**: Disable for specific paths like `/api/`
4. **Early redirect**: Redirects before processing the request (no wasted computation)
5. **Query string preservation**: Full URI preserved during redirect

## Requirements

- PHP 8.3 or higher
- ctw/ctw-middleware ^4.0
- ctw/ctw-http ^4.0

## Installation

Install by adding the package as a [Composer](https://getcomposer.org) requirement:

```bash
composer require ctw/ctw-middleware-trailingslash
```

## Usage Examples

### Basic Pipeline Registration (Mezzio)

```php
use Ctw\Middleware\TrailingSlashMiddleware\TrailingSlashMiddleware;

// In config/pipeline.php - place early in the pipeline
$app->pipe(TrailingSlashMiddleware::class);
```

### ConfigProvider Registration

```php
// config/config.php
return [
    // ...
    \Ctw\Middleware\TrailingSlashMiddleware\ConfigProvider::class,
];
```

### Redirect Behavior

| Request URL | Result | Status |
|-------------|--------|--------|
| `/about` | Redirect to `/about/` | 301 |
| `/about/` | Pass through | - |
| `/page?id=1` | Redirect to `/page/?id=1` | 301 |
| `/style.css` | Pass through (has extension) | - |
| `/image.png` | Pass through (has extension) | - |
| `/api/users` | Pass through (if excluded) | - |
| `/` | Pass through (root) | - |

### Path Exclusions

Exclude specific paths from trailing slash processing:

```php
// config/autoload/trailingslash.global.php
return [
    'trailing_slash_middleware' => [
        'path_disable' => [
            '/api/',
            '/webhook/',
            '/.well-known/',
        ],
    ],
];
```

### File Extension Handling

The middleware automatically detects file extensions and skips processing:

```php
// These URLs are NOT redirected:
/assets/style.css
/images/logo.png
/downloads/report.pdf
/scripts/app.js

// These URLs ARE redirected (no extension):
/about        → /about/
/products     → /products/
/contact      → /contact/
```

### Query String Preservation

Query strings are preserved during redirect:

```
/search?q=test → /search/?q=test
/page?id=1&sort=name → /page/?id=1&sort=name
```

### Response Header

```http
HTTP/1.1 301 Moved Permanently
Location: https://example.com/about/
```

### Testing with cURL

```bash
# Check redirect behavior
curl -I https://example.com/about

# Expected response:
# HTTP/1.1 301 Moved Permanently
# Location: https://example.com/about/
```

### Nginx Equivalent

This middleware replaces the need for nginx rewrite rules like:

```nginx
# No longer needed with this middleware
rewrite ^([^.]*[^/])$ $1/ permanent;
```

### Why Trailing Slashes Matter

1. **SEO**: Google treats `/page` and `/page/` as different URLs
2. **Relative links**: `<a href="sub">` resolves differently based on trailing slash
3. **Caching**: CDNs may cache multiple versions of the same page
4. **Analytics**: Traffic may be split across URL variations

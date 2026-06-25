# PHP 8.5 Migration — `ctw/ctw-middleware-trailingslash`

- **Branch:** `php85` (cut from `master`)
- **Runtime:** PHP 8.3.31 → **8.5.7**
- **PHPUnit:** 12 → **13.2.1**
- **Status:** ✅ done

PSR-15 middleware that appends a trailing slash and redirects with an HTTP 301.
It depends on **`ctw/ctw-http ^4.0`** and **`ctw/ctw-middleware`**. Under PHP 8.5
the original `composer update -W` failed because `ctw/ctw-middleware ^4.0` pins
`laminas/laminas-diactoros` 2.x (caps PHP at `~8.3.0`); the fix is
`ctw/ctw-middleware: dev-php85` (diactoros → ^3, middlewares/utils → ^4, which
clears five vendor "implicitly nullable parameter" deprecations). Beyond the
shared bump the **first-party** work here is a test-double cleanup required by
PHPUnit 13.

---

## Audit checklist

### `test/` — first-party (PHPUnit 13 test doubles)

- [x] **(tooling) `test/TrailingSlashMiddlewareFactoryTest.php`, `test/TrailingSlashMiddlewareTest.php`** — PHPUnit 13 emits "mock object without expectations" notices for `createMock()` doubles that only stub return values. The factory test and the `getInstanceWithConfig()` helper used `createMock()` purely as stubs.
  **Fix:** migrated those stub-style doubles to `createStub()` and dropped the now-redundant `->with('config')` argument-match constraints (the stubbed `has()` / `get()` returns are fixed regardless of argument). `createMock()` was kept where `->expects()` is genuinely used (the two `testInvokeOnlyCallsContainerGetWhenConfigExists` / `…NeverCalled` cases). This reduced the assertion count from 109 to 85 — the dropped `with()` constraints were the removed assertions — but all 53 tests still pass and verify behavior.
- [x] **(tooling) `test/TrailingSlashMiddlewareFactoryTest.php` (×7), `test/TrailingSlashMiddlewareTest.php` (×1)** — PHPStan `staticMethod.dynamicCall`: `createStub()` is a static method on `TestCase`, so calling it as `$this->createStub(...)` is a dynamic call to a static method.
  **Fix:** changed the eight new `$this->createStub(ContainerInterface::class)` calls to `self::createStub(ContainerInterface::class)`. PHPStan is now clean.

### Vendor (cleared by `ctw/ctw-middleware: dev-php85`)

- [x] **(deprecation) `vendor/middlewares/utils`** — five "implicitly nullable parameter" deprecations (`Factory::createUploadedFile()` `$size`/`$filename`/`$mediaType`; `Dispatcher::run()` `$request`; `CallableHandler::__construct()` `$responseFactory`).
  **Fix:** not fixable in this repo's `src/`. Cleared by `middlewares/utils` v4 (4.0.2), pulled in via `ctw/ctw-middleware: dev-php85` (which also installs diactoros 3.8.0 and servicemanager 4.5.1).

### Tooling

- [x] **(tooling) PHPUnit 12 → 13.** Suite runs green on PHPUnit 13.2.1.
  **Fix:** `phpunit/phpunit ^12 → ^13`, `ctw/ctw-qa → dev-php85`, `phpunit.xml.dist` schema → 13.2.
- [x] **(tooling) PHPStan `missingType.*` unmatched-ignore.** Resolved centrally in `ctw/ctw-qa` (`reportUnmatchedIgnoredErrors: false`) via `ctw/ctw-qa: dev-php85`.

---

## composer.json & CI

- [x] `require.php`: `^8.3` → **`^8.5`**.
- [x] `ctw/ctw-http`: **`^4.0`** (4.0.6) — unblocked on its own; left at `^4.0`.
- [x] `ctw/ctw-middleware`: `^4.0` → **`dev-php85`** — brings diactoros ^3 (3.8.0) + middlewares/utils ^4 (4.0.2); unblocks `composer update -W`. Re-tag to a stable release before merge.
- [x] `ctw/ctw-qa`: `^5.0` → **`dev-php85`**. Re-tag before merge.
- [x] `phpunit/phpunit`: `^12.0` → **`^13.0`** (installs 13.2.1).
- [x] `phpunit.xml.dist`: schema → 13.2.
- [x] `.github/workflows/tests.yml`: matrix → **PHP 8.5 only** (`php: [ '8.5' ]`).

---

## Final audit (PHP 8.5.7)

- [x] `php -v` → **PHP 8.5.7** (cli).
- [x] `composer update -W` → **clean** (rc=0, nothing to modify; no security advisories).
- [x] `phpunit --no-coverage --display-deprecations --display-warnings --display-notices --display-errors` → **53 tests, 85 assertions, 0 issues** (PHPUnit 13.2.1 / PHP 8.5.7).
- [x] PHPStan → **clean** (no issues found) after the `self::createStub` fix above.

# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [3.0.0] - 2026-08-11

### Added

- **Rich `Result` DTO:** `placeId`, `types`, `viewport`, `bounds`, `partialMatch`, and `plusCode` when present in the Google payload.
- **Optional response cache:** `geocode.cache.enabled`, `geocode.cache.store`, and `geocode.cache.ttl` (env: `GEOCODE_CACHE_*`). Cache keys include query parameters and the configured default language.
- **Batch geocoding:** `Geocode::addresses()` and `Geocode::latLngs()` using Laravel `Http::pool` for concurrent requests.
- **Batch throttle:** `geocode.throttle.per_second` (env: `GEOCODE_THROTTLE_PER_SECOND`) to cap requests per second across batch chunks.
- **Fluent query builder:** `Geocode::query()` with `region()`, `language()`, `components()`, and `bounds()` before `address()`, `latLng()`, or `placeId()`.
- **Observability:** `GeocodingPerformed` event (params, result, duration, status, cached flag) and debug log line on every lookup.
- **Configurable HTTP:** `geocode.timeout`, `geocode.retry.times`, and `geocode.retry.sleep` (env: `GEOCODE_TIMEOUT`, `GEOCODE_RETRY_*`).
- **Static analysis:** PHPStan level 8 on `src/` with a dedicated CI job.

### Changed

- **Breaking:** `Result` properties are now `readonly` (requires PHP **8.1+**).
- **Breaking:** `Result::$postalCode` is `?string`; when no postal code is present in the Google payload, the value is `null` instead of `false`.
- **Breaking:** Minimum PHP for new features is **8.1+** due to `readonly` on `Result` and `GeocodingPerformed`.
- Config expanded with `timeout`, `retry`, `cache`, and `throttle` sections alongside existing `api_key` and `language` keys.

### Fixed

- Production warning when `geocode.api_key` is empty (logged once via the service container binding).

## [2.1.0] - 2026-08-11

### Added
- Laravel 13 support.

## [2.0.0] - 2026-08-11

### Added
- Laravel 9–12 support via `illuminate/http` and `illuminate/support`.
- `Result` value object with property access (`latitude`, `longitude`, `formattedAddress`, `locationType`, `postalCode`, `raw`), incorporating the approach from PR #17 by @Niellles.
- `GeocodingFailedException` for real HTTP/network failures.
- orchestra/testbench test suite with `Http::fake` coverage for geocoding, reverse geocoding, Place ID lookup, custom params, retries, and the null/throw error model.
- GitHub Actions CI matrix (Laravel 9–13 × compatible PHP, prefer-lowest/prefer-stable, `composer audit`, Pint).
- `UPGRADING.md` for the 1.x → 2.0 migration.

### Changed
- HTTP transport now uses Laravel's `Http` facade (no direct `guzzlehttp/guzzle` constraint).
- Service provider rebuilt with `spatie/laravel-package-tools`.
- Autoloading migrated from PSR-0 to PSR-4.
- Config keys renamed to `geocode.api_key` / `geocode.language` (env: `GEOCODE_API_KEY`, `GEOCODE_LANGUAGE`).
- Support floor raised from Laravel 8 to 9 (`spatie/laravel-package-tools` requires illuminate ^9.28+).

### Removed
- `Geocode::make()` factory.
- Direct Guzzle client usage and the `~5.3|~6.0` constraint.
- Legacy Laravel 4 / Lumen-specific provider branches.

### Fixed
- Silent failure of config publishing caused by missing `LaravelApplication` / `LumenApplication` imports in the old provider.

## [2.0.0-beta1] - 2026-08-11

### Added
- Initial 2.x beta targeting Laravel 9–13 modernization.

## [1.5.0] - 2026-08-11

### Security
- Raised `guzzlehttp/guzzle` to `^6.5.8` and dropped the unmaintained `~5.3` range.
- **Final security release for the 1.x line** (then frozen). Residual Guzzle advisories that only have fixes in 7.x are addressed by upgrading to 2.0.

[Unreleased]: https://github.com/jotafurtado/geocode/compare/3.0.0...HEAD
[3.0.0]: https://github.com/jotafurtado/geocode/compare/2.1.0...3.0.0
[2.1.0]: https://github.com/jotafurtado/geocode/compare/2.0.0...2.1.0
[2.0.0]: https://github.com/jotafurtado/geocode/compare/2.0.0-beta1...2.0.0
[2.0.0-beta1]: https://github.com/jotafurtado/geocode/compare/1.5.0...2.0.0-beta1
[1.5.0]: https://github.com/jotafurtado/geocode/compare/1.4.0...1.5.0

# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/jotafurtado/geocode/compare/2.0.0...HEAD
[2.0.0]: https://github.com/jotafurtado/geocode/compare/2.0.0-beta1...2.0.0
[2.0.0-beta1]: https://github.com/jotafurtado/geocode/compare/1.5.0...2.0.0-beta1
[1.5.0]: https://github.com/jotafurtado/geocode/compare/1.4.0...1.5.0

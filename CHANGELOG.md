# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.0.0-beta1] - 2026-08-11

### Added
- Laravel 8–12 support via `illuminate/http` and `illuminate/support`.
- `Result` value object with property access (`latitude`, `longitude`, `formattedAddress`, `locationType`, `postalCode`, `raw`), incorporating the approach from PR #17 by @Niellles.
- `GeocodingFailedException` for real HTTP/network failures.
- orchestra/testbench test suite with `Http::fake` coverage for geocoding, reverse geocoding, Place ID lookup, and the null/throw error model.
- GitHub Actions CI matrix (Laravel 8–12 × compatible PHP, prefer-lowest/prefer-stable, `composer audit`, Pint).
- `UPGRADING.md` for the 1.x → 2.0 migration.

### Changed
- HTTP transport now uses Laravel's `Http` facade (no direct `guzzlehttp/guzzle` constraint).
- Service provider rebuilt with `spatie/laravel-package-tools`.
- Autoloading migrated from PSR-0 to PSR-4.
- Config keys renamed to `geocode.api_key` / `geocode.language` (env: `GEOCODE_API_KEY`, `GEOCODE_LANGUAGE`).

### Removed
- `Geocode::make()` factory.
- Direct Guzzle client usage and the `~5.3|~6.0` constraint.
- Legacy Laravel 4 / Lumen-specific provider branches.

### Fixed
- Silent failure of config publishing caused by missing `LaravelApplication` / `LumenApplication` imports in the old provider.

## [1.5.0] - 2026-08-11

### Security
- Raised `guzzlehttp/guzzle` to `^6.5.8` and dropped the unmaintained `~5.3` range.
- **Final security release for the 1.x line** (then frozen). Residual Guzzle advisories that only have fixes in 7.x are addressed by upgrading to 2.0.

[Unreleased]: https://github.com/jotafurtado/geocode/compare/2.0.0-beta1...HEAD
[2.0.0-beta1]: https://github.com/jotafurtado/geocode/compare/1.5.0...2.0.0-beta1
[1.5.0]: https://github.com/jotafurtado/geocode/compare/1.4.0...1.5.0

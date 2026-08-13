# One 3.0 release supporting Laravel 9 through 13

We ship a single 3.0 with broad constraints (`illuminate/*: ^9.0 || ^10.0 || ^11.0 || ^12.0 || ^13.0`, PHP **8.1+** for `readonly` on `Result`) instead of splitting per Laravel generation. The `Http` facade and the `ServiceProvider`/`Facade` base classes are stable across that range, and `spatie/laravel-package-tools` requires `illuminate/contracts` ^9.28+, which rules out Laravel 8. The CI matrix (L9–13 × PHP) validates the release.

This continues the strategy established in 2.0 (see git history for ADR-0002). 2.x remains available for PHP 8.0 consumers who do not need 3.0 features.

Considered: including Laravel 8 (rejected — incompatible with the chosen Spatie provider tooling without dropping Spatie); a per-generation split (e.g. 3.0 for L9–10, 4.0 for L11+) — rejected as premature double-maintenance for a thin wrapper. We split only if a real incompatibility surfaces in CI.

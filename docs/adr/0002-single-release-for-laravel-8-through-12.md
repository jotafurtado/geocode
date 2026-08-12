# One 2.0 release supporting Laravel 9 through 13

We ship a single 2.0 with broad constraints (`illuminate/*: ^9.0 || ^10.0 || ^11.0 || ^12.0 || ^13.0`, `php: ^8.0`) instead of splitting per Laravel generation. The `Http` facade and the `ServiceProvider`/`Facade` base classes are stable across that range, and `spatie/laravel-package-tools` requires `illuminate/contracts` ^9.28+, which rules out Laravel 8. The CI matrix (L9–13 × PHP) validates the release.

Considered: including Laravel 8 (rejected — incompatible with the chosen Spatie provider tooling without dropping Spatie); a 2.0 (L9–10) + 3.0 (L11+) split — rejected as premature double-maintenance for a thin wrapper. We split only if a real incompatibility surfaces in CI.

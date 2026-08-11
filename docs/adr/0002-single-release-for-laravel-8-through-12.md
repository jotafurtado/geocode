# One 2.0 release supporting Laravel 8 through 12

We ship a single 2.0 with broad constraints (`illuminate/*: ^8.0 || ^9.0 || ^10.0 || ^11.0 || ^12.0`, `php: ^8.0`) instead of splitting per Laravel generation. The `Http` facade and the `ServiceProvider`/`Facade` base classes are stable across L8–L12, so one codebase suffices; the CI matrix (L8–12 × PHP) validates it.

Considered: a 2.0 (L8–10) + 3.0 (L11+) split — rejected as premature double-maintenance for a thin wrapper. We split only if a real incompatibility surfaces in CI.

# Upgrading from 1.x to 2.0

## Requirements

- PHP **8.0+**
- Laravel **9–13**

Laravel 4–8 users should stay on **1.5.0** (final 1.x security release) or upgrade the framework first.

## Dependency

```sh
composer require jcf/geocode:^2.0
```

2.0 no longer requires `guzzlehttp/guzzle` directly. HTTP goes through Laravel's `Http` facade.

## Environment / config

| 1.x | 2.0 |
| --- | --- |
| `GEOCODE_GOOGLE_APIKEY` | `GEOCODE_API_KEY` |
| `GEOCODE_GOOGLE_LANGUAGE` | `GEOCODE_LANGUAGE` |
| `config('geocode.apikey')` | `config('geocode.api_key')` |

Re-publish config if you customize it:

```sh
php artisan vendor:publish --tag=geocode-config
```

## API changes

### `make()` removed

```php
// 1.x
$response = Geocode::make()->address('1 Infinite Loop');

// 2.0
$result = Geocode::address('1 Infinite Loop');
```

The facade resolves the container singleton; you can also inject `Jcf\Geocode\Geocode`.

### `Response` → `Result` with property access

```php
// 1.x
$response->latitude();
$response->formattedAddress();

// 2.0
$result->latitude;
$result->formattedAddress;
$result->postalCode;
$result->raw;
```

### Error model

| Situation | 1.x | 2.0 |
| --- | --- | --- |
| Google non-OK status (`ZERO_RESULTS`, `REQUEST_DENIED`, …) | `false` | `null` |
| HTTP / network failure | often surfaced as `false` / hard failure | `GeocodingFailedException` |

```php
use Jcf\Geocode\Exceptions\GeocodingFailedException;

try {
    $result = Geocode::address($address);
} catch (GeocodingFailedException $e) {
    // real transport / HTTP failure
}

if ($result === null) {
    // not found / denied / quota / invalid request
}
```

## Upgrading from 2.x to 3.0

### Requirements

- PHP **8.1+** (`Result` and `GeocodingPerformed` use `readonly` properties)
- Laravel **9–13** (unchanged support range)

```sh
composer require jcf/geocode:^3.0
```

### `Result::$postalCode`

In 2.x, a missing postal code was represented as `false`. In 3.0 it is `null`:

```php
// 2.x
if ($result->postalCode === false) {
    // no postal code
}

// 3.0
if ($result->postalCode === null) {
    // no postal code
}
```

### Immutable `Result`

All `Result` properties are `readonly`. Assigning to them after construction will throw.

### Config: `api_key` vs cache / HTTP settings

2.x config only exposed `geocode.api_key` and `geocode.language`. **3.0 keeps those keys unchanged** but adds separate sections for HTTP, cache, and batch throttle. They are independent concerns:

| Concern | Config keys | Env vars |
| --- | --- | --- |
| Google credentials | `geocode.api_key` | `GEOCODE_API_KEY` |
| Default language | `geocode.language` | `GEOCODE_LANGUAGE` |
| HTTP timeout / retry | `geocode.timeout`, `geocode.retry.*` | `GEOCODE_TIMEOUT`, `GEOCODE_RETRY_*` |
| Response cache | `geocode.cache.*` | `GEOCODE_CACHE_*` |
| Batch rate limit | `geocode.throttle.per_second` | `GEOCODE_THROTTLE_PER_SECOND` |

Re-publish config to pick up the new keys:

```sh
php artisan vendor:publish --tag=geocode-config --force
```

Cache is **disabled by default**. Enable explicitly when you want to avoid repeat Google calls:

```sh
GEOCODE_CACHE_ENABLED=true
GEOCODE_CACHE_STORE=redis
GEOCODE_CACHE_TTL=86400
```

Cache keys hash the full query parameters **and** the configured default language, so changing `GEOCODE_LANGUAGE` invalidates prior cache entries for the same address.

### New APIs (non-breaking additions)

- `Geocode::addresses()` / `Geocode::latLngs()` for batch lookups
- `Geocode::query()` fluent builder (`region`, `language`, `components`, `bounds`)
- `Jcf\Geocode\Events\GeocodingPerformed` dispatched on every lookup
- Rich fields on `Result`: `placeId`, `types`, `viewport`, `bounds`, `partialMatch`, `plusCode`

See [README.md](README.md) for usage examples and [CHANGELOG.md](CHANGELOG.md) for the full 3.0.0 release notes.

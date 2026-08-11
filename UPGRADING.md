# Upgrading from 1.x to 2.0

## Requirements

- PHP **8.0+**
- Laravel **8–12**

Laravel 4–7 users should stay on **1.5.0** (final 1.x security release) or upgrade the framework first.

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

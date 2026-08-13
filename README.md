# Google Geocoding API for Laravel

[![Latest Stable Version](https://poser.pugx.org/jcf/geocode/v/stable.svg)](https://packagist.org/packages/jcf/geocode)
[![Total Downloads](https://poser.pugx.org/jcf/geocode/downloads.svg)](https://packagist.org/packages/jcf/geocode)
[![License](https://poser.pugx.org/jcf/geocode/license.svg)](https://packagist.org/packages/jcf/geocode)
[![Tests](https://github.com/jotafurtado/geocode/actions/workflows/tests.yml/badge.svg)](https://github.com/jotafurtado/geocode/actions/workflows/tests.yml)

A simple Laravel service provider for the Google Maps Geocoding API.

**3.x** supports **Laravel 9–13** (PHP 8.1+). For **2.x** (Laravel 9–13, PHP 8.0+), use `^2.0`. For legacy Laravel 4–8, use **1.5.0** (final 1.x security release).

## Installation

```sh
composer require jcf/geocode:^3.0
```

The service provider and `Geocode` facade are auto-discovered.

Optionally publish the config:

```sh
php artisan vendor:publish --tag=geocode-config
```

## Configuration

Add to your `.env`:

```sh
GEOCODE_API_KEY=<your_google_api_key>
GEOCODE_LANGUAGE=en   # optional: pt-BR, es, de, it, fr, en-GB, …

# HTTP (optional)
GEOCODE_TIMEOUT=10
GEOCODE_RETRY_TIMES=2
GEOCODE_RETRY_SLEEP=100

# Cache (optional, disabled by default)
GEOCODE_CACHE_ENABLED=false
GEOCODE_CACHE_STORE=redis
GEOCODE_CACHE_TTL=86400

# Batch throttle (optional, 0 = no throttle)
GEOCODE_THROTTLE_PER_SECOND=0
```

[Supported languages](https://developers.google.com/maps/faq#languagesupport) for the Google Maps Geocoding API.

The API key remains optional in config; when omitted, Google typically responds with `REQUEST_DENIED` and the package returns `null`.

## Usage

### Geocoding (address → coordinates)

```php
use Jcf\Geocode\Facades\Geocode;

$result = Geocode::address('1 Infinite Loop');

if ($result) {
    echo $result->latitude;
    echo $result->longitude;
    echo $result->formattedAddress;
    echo $result->locationType;
    echo $result->postalCode; // ?string — null when absent
}
```

### Reverse geocoding (coordinates → address)

```php
$result = Geocode::latLng(40.7637931, -73.9722014);

if ($result) {
    echo $result->formattedAddress;
}
```

### Place ID lookup

```php
$result = Geocode::placeId('ChIJdd4hrwug2EcRmSrV3Vo6llI');
```

### Rich `Result` fields

When Google returns them, `Result` also exposes:

```php
$result->placeId;      // ?string
$result->types;        // ?array<int, string>
$result->viewport;     // ?object
$result->bounds;       // ?object
$result->partialMatch; // ?bool
$result->plusCode;      // ?object
$result->raw;          // first Google result object
```

All properties are `readonly`.

### Batch

Geocode many addresses or coordinate pairs concurrently via Laravel's `Http::pool`:

```php
$results = Geocode::addresses(['Address A', 'Address B', 'Address C']);
// array<int, Result|null>

$results = Geocode::latLngs([
    ['lat' => 37.331741, 'lng' => -122.0303329],
    ['lat' => 40.7637931, 'lng' => -73.9722014],
]);
```

When `GEOCODE_THROTTLE_PER_SECOND` is greater than zero, requests are sent in batches of that size with a one-second pause between batches.

### Cache

Enable caching to avoid repeat calls to Google for the same query:

```php
// .env
GEOCODE_CACHE_ENABLED=true
GEOCODE_CACHE_STORE=redis
GEOCODE_CACHE_TTL=86400
```

Cache keys include the query parameters and the configured default language. Cached hits skip HTTP and are marked in `GeocodingPerformed` events (`cached: true`).

### Fluent builder

Chain Google API parameters before executing a lookup:

```php
$result = Geocode::query()
    ->region('br')
    ->language('pt-BR')
    ->components(['country' => 'BR'])
    ->bounds([
        'southwest' => ['lat' => -23.6, 'lng' => -46.7],
        'northeast' => ['lat' => -23.5, 'lng' => -46.6],
    ])
    ->address('Av Paulista');
```

The builder supports `address()`, `latLng()`, and `placeId()`.

### Events

Every lookup (including cache hits and batch items) dispatches `Jcf\Geocode\Events\GeocodingPerformed`:

```php
use Illuminate\Support\Facades\Event;
use Jcf\Geocode\Events\GeocodingPerformed;

Event::listen(GeocodingPerformed::class, function (GeocodingPerformed $event) {
    // $event->params   — query parameters sent to Google
    // $event->result   — Result|null
    // $event->durationMs
    // $event->status   — Google status or cached equivalent
    // $event->cached    — true when served from cache
});
```

Debug logging is also written to the default log channel (`Geocoding performed`).

### Errors

- Google non-OK status (`ZERO_RESULTS`, `REQUEST_DENIED`, `OVER_QUERY_LIMIT`, …) → `null`
- HTTP / connection failure → `Jcf\Geocode\Exceptions\GeocodingFailedException`

```php
use Jcf\Geocode\Exceptions\GeocodingFailedException;

try {
    $result = Geocode::address($address);
} catch (GeocodingFailedException $e) {
    // transport / HTTP failure
}
```

## Testing

In your application or feature tests, fake outbound HTTP with Laravel's `Http` facade. The package calls `https://maps.googleapis.com/maps/api/geocode/json`.

### Geocoding

```php
use Illuminate\Support\Facades\Http;
use Jcf\Geocode\Facades\Geocode;

Http::fake([
    'maps.googleapis.com/*' => Http::response([
        'status' => 'OK',
        'results' => [[
            'formatted_address' => '1 Infinite Loop, Cupertino, CA 95014, USA',
            'geometry' => [
                'location' => ['lat' => 37.331741, 'lng' => -122.0303329],
                'location_type' => 'ROOFTOP',
            ],
            'address_components' => [],
        ]],
    ], 200),
]);

$result = Geocode::address('1 Infinite Loop');

$this->assertNotNull($result);
$this->assertSame(37.331741, $result->latitude);
Http::assertSentCount(1);
```

### Reverse geocoding

```php
Http::fake([
    'maps.googleapis.com/*' => Http::response([
        'status' => 'OK',
        'results' => [[
            'formatted_address' => '767 5th Avenue, New York, NY 10153, USA',
            'geometry' => [
                'location' => ['lat' => 40.7637931, 'lng' => -73.9722014],
                'location_type' => 'ROOFTOP',
            ],
            'address_components' => [],
        ]],
    ], 200),
]);

$result = Geocode::latLng(40.7637931, -73.9722014);
$this->assertNotNull($result);
```

### Batch

```php
Http::fake([
    'maps.googleapis.com/*' => Http::response([
        'status' => 'OK',
        'results' => [[
            'formatted_address' => '1 Infinite Loop, Cupertino, CA 95014, USA',
            'geometry' => [
                'location' => ['lat' => 37.331741, 'lng' => -122.0303329],
                'location_type' => 'ROOFTOP',
            ],
            'address_components' => [],
        ]],
    ], 200),
]);

$results = Geocode::addresses(['A', 'B', 'C']);

$this->assertCount(3, $results);
Http::assertSentCount(3);
```

### Cache

```php
use Illuminate\Support\Facades\Cache;

config([
    'geocode.cache.enabled' => true,
    'geocode.cache.store' => 'array',
    'cache.default' => 'array',
    'cache.stores.array' => ['driver' => 'array'],
]);

Cache::store('array')->clear();

Http::fake([
    'maps.googleapis.com/*' => Http::response([
        'status' => 'OK',
        'results' => [[
            'formatted_address' => '1 Infinite Loop, Cupertino, CA 95014, USA',
            'geometry' => [
                'location' => ['lat' => 37.331741, 'lng' => -122.0303329],
                'location_type' => 'ROOFTOP',
            ],
            'address_components' => [],
        ]],
    ], 200),
]);

Geocode::address('1 Infinite Loop');
Geocode::address('1 Infinite Loop');

Http::assertSentCount(1); // second lookup served from cache
```

### Package development

```sh
composer test
composer format   # Laravel Pint
composer analyse  # PHPStan level 8
```

## Upgrading

- [1.x → 2.0](UPGRADING.md#upgrading-from-1x-to-20)
- [2.x → 3.0](UPGRADING.md#upgrading-from-2x-to-30)

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Credits

- [João Carlos](https://github.com/jotafurtado)
- Property access on results inspired by [#17](https://github.com/jotafurtado/geocode/pull/17) from [@Niellles](https://github.com/Niellles)
- [All contributors](https://github.com/jotafurtado/geocode/graphs/contributors)

## License

MIT

# Google Geocoding API for Laravel

[![Latest Stable Version](https://poser.pugx.org/jcf/geocode/v/stable.svg)](https://packagist.org/packages/jcf/geocode)
[![Total Downloads](https://poser.pugx.org/jcf/geocode/downloads.svg)](https://packagist.org/packages/jcf/geocode)
[![License](https://poser.pugx.org/jcf/geocode/license.svg)](https://packagist.org/packages/jcf/geocode)
[![Tests](https://github.com/jotafurtado/geocode/actions/workflows/tests.yml/badge.svg)](https://github.com/jotafurtado/geocode/actions/workflows/tests.yml)

A simple Laravel service provider for the Google Maps Geocoding API.

**2.x** supports **Laravel 9–13** (PHP 8.0+). For legacy Laravel 4–8, use **1.5.0** (final 1.x security release).

## Installation

```sh
composer require jcf/geocode:^2.0
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
    echo $result->postalCode;
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

### Raw Google payload

```php
$result = Geocode::address('1 Infinite Loop');

if ($result) {
    $result->raw; // first Google result object
}
```

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

## Upgrading from 1.x

See [UPGRADING.md](UPGRADING.md).

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Testing

```sh
composer test
```

## Credits

- [João Carlos](https://github.com/jotafurtado)
- Property access on results inspired by [#17](https://github.com/jotafurtado/geocode/pull/17) from [@Niellles](https://github.com/Niellles)
- [All contributors](https://github.com/jotafurtado/geocode/graphs/contributors)

## License

MIT

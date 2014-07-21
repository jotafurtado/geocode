# Google Geocoding API for Laravel 4

[![Latest Stable Version](https://poser.pugx.org/jcf/geocode/v/stable.svg)](https://packagist.org/packages/jcf/geocode) [![Total Downloads](https://poser.pugx.org/jcf/geocode/downloads.svg)](https://packagist.org/packages/jcf/geocode) [![License](https://poser.pugx.org/jcf/geocode/license.svg)](https://packagist.org/packages/jcf/geocode)

A simple [Laravel 4](http://four.laravel.com/) service provider for Google Geocoding API.

## Installation

This package can be installed via [Composer](http://getcomposer.org) by requiring the
`jcf/geocode` package in your project's `composer.json`.

```json
{
    "require": {
        "jcf/geocode": "1.0.*"
    }
}
```

Then run a composer update
```sh
php composer.phar update
```

After updating composer, add the ServiceProvider to the providers array in app/config/app.php

```php
'Jcf\Geocode\GeocodeServiceProvider',
```
Add then alias Geocode adding its facade to the aliases array in the same file :

```php
'Geocode' => 'Jcf\Geocode\Facades\Geocode'
```

## Usage
You can find data from addresses:
```php
$response = Geocode::make()->address('1 Infinite Loop');

if ($response) {
	echo $response->latitude();
	echo $response->longitude();
	echo $response->formattedAddress();
	echo $response->locationType();
}

// Output
// 37.331741
// -122.0303329
// 1 Infinite Loop, Cupertino, CA 95014, USA
// ROOFTOP
```

Or from latitude/longitude:

```php
$response = Geocode::make()->latLng(40.7637931,-73.9722014);
if ($response) {
	echo $response->latitude();
	echo $response->longitude();
	echo $response->formattedAddress();
	echo $response->locationType();
}

// Output
// 40.7637931
// -73.9722014
// 767 5th Avenue, New York, NY 10153, USA
// ROOFTOP

```

If you need other data rather than formatted address, latitude, longitude or location type, you can use the `raw()` method:
```php
$response = Geocode::make()->latLng(40.7637931,-73.9722014);
if ($response) {
	echo $response->raw()->address_components[8]['types'][0];
	echo $response->raw()->address_components[8]['long_name'];
}

// Output
// postal_code
// 10153
```

That's it. Pull requests are welcome.
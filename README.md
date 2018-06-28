# Google Geocoding API for Laravel

[![Latest Stable Version](https://poser.pugx.org/jcf/geocode/v/stable.svg)](https://packagist.org/packages/jcf/geocode) [![Total Downloads](https://poser.pugx.org/jcf/geocode/downloads.svg)](https://packagist.org/packages/jcf/geocode) [![License](https://poser.pugx.org/jcf/geocode/license.svg)](https://packagist.org/packages/jcf/geocode)

A simple [Laravel](http://laravel.com/) service provider for Google Geocoding API.

## Installation

This package can be installed via [Composer](http://getcomposer.org).

Run composer require command.

```sh
composer require "jcf/geocode":"~1.3"
```

### Laravel 5.5

Both the service provider and alias will be automatically installed by Laravel 5.5 package discovery. If you don't use auto discovery, follow the instructions for Laravel 5.0-5.4 below:

### Laravel 5.0-5.4

After updating composer, add the service provider to the providers array in app/config/app.php

```php
Jcf\Geocode\GeocodeServiceProvider::class,
```
Add then alias Geocode adding its facade to the aliases array in the same file :

```php
'Geocode' => Jcf\Geocode\Facades\Geocode::class,
```

### Laravel 4

After updating composer, add the service provider to the providers array in app/config/app.php

```php
'Jcf\Geocode\GeocodeServiceProvider',
```
Add then alias Geocode adding its facade to the aliases array in the same file :

```php
'Geocode' => 'Jcf\Geocode\Facades\Geocode'
```

### Lumen 5

After updating composer, register the service provider in bootstrap/app.php

```php
$app->register(Jcf\Geocode\GeocodeServiceProvider::class);
```

Since facade are not enabled by default on Lumen, don't forget to add this.

```php
use Jcf\Geocode\Geocode;

```

## Configuration

Add the following line to the .env file:

```sh
GEOCODE_GOOGLE_APIKEY=<your_google_api_key>
```

You can optionally set the response language.

```sh
GEOCODE_GOOGLE_LANGUAGE=en # pt-BR, es, de, it, fr, en-GB

```

[Supported Languages](https://developers.google.com/maps/faq?hl=en#languagesupport) for Google Maps Geocoding API.


## Usage
You can find data from addresses:
```php
$response = Geocode::make()->address('1 Infinite Loop');

if ($response) {
	echo $response->latitude;
	echo $response->longitude;
	echo $response->formattedAddress;
	echo $response->locationType;
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
	echo $response->latitude;
	echo $response->longitude;
	echo $response->formattedAddress;
	echo $response->locationType;
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

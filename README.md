# Google Geocode Service Provider for Laravel 4

[![Latest Stable Version](https://poser.pugx.org/jcf/geocode/v/stable.svg)](https://packagist.org/packages/jcf/geocode) [![Total Downloads](https://poser.pugx.org/jcf/geocode/downloads.svg)](https://packagist.org/packages/jcf/geocode) [![License](https://poser.pugx.org/jcf/geocode/license.svg)](https://packagist.org/packages/jcf/geocode)

A simple [Laravel 4](http://four.laravel.com/) service provider for Google Geocode API.
## Installation

The package can be installed via [Composer](http://getcomposer.org) by requiring the
`jcf/geocode` package in your project's `composer.json`.

```json
{
    "require": {
        "jcf/geocode": "dev-master"
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

## Usage

```php
	$address = Geocode::make()->address('1 Infinite Loop');
	if($address){
		echo $address->latitude();
		echo $address->longitude();
		echo $address->formatted();
	}

//Output
37.331741
-122.0303329
1 Infinite Loop, Cupertino, CA 95014, USA
```

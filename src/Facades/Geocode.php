<?php

namespace Jcf\Geocode\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Jcf\Geocode\Result|null address(string $address, array $params = [])
 * @method static \Jcf\Geocode\Result|null latLng(float|string $lat, float|string $lng)
 * @method static \Jcf\Geocode\Result|null placeId(string $id)
 *
 * @see \Jcf\Geocode\Geocode
 */
class Geocode extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'geocode';
    }
}

<?php

namespace Jcf\Geocode\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Jcf\Geocode\GeocodeQueryBuilder query()
 * @method static \Jcf\Geocode\Result|null address(string $address, array<string, mixed> $params = [])
 * @method static array<int, \Jcf\Geocode\Result|null> addresses(array<int, string> $addresses, array<string, mixed> $params = [])
 * @method static \Jcf\Geocode\Result|null latLng(float|string $lat, float|string $lng, array<string, mixed> $params = [])
 * @method static array<int, \Jcf\Geocode\Result|null> latLngs(array<int, array{lat?: float|string|null, lng?: float|string|null}> $coords)
 * @method static \Jcf\Geocode\Result|null placeId(string $id, array<string, mixed> $params = [])
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

<?php

namespace Jcf\Geocode\Tests;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Jcf\Geocode\Facades\Geocode;
use Jcf\Geocode\GeocodeServiceProvider;
use Jcf\Geocode\Result;
use Orchestra\Testbench\TestCase;

class GeocodeCacheTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [GeocodeServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Geocode' => Geocode::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('geocode.api_key', 'test-key');
        $app['config']->set('geocode.language', 'en');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('cache.stores.array', [
            'driver' => 'array',
        ]);
        $app['config']->set('geocode.cache.enabled', true);
        $app['config']->set('geocode.cache.store', 'array');
        $app['config']->set('geocode.cache.ttl', 86400);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Cache::store('array')->clear();
    }

    private function fakeSuccessfulGeocode(): void
    {
        Http::fake([
            'maps.googleapis.com/*' => Http::response([
                'status' => 'OK',
                'results' => [[
                    'formatted_address' => '1 Infinite Loop, Cupertino, CA 95014, USA',
                    'geometry' => [
                        'location' => [
                            'lat' => 37.331741,
                            'lng' => -122.0303329,
                        ],
                        'location_type' => 'ROOFTOP',
                    ],
                    'address_components' => [],
                ]],
            ], 200),
        ]);
    }

    public function test_address_uses_cache_on_second_lookup(): void
    {
        $this->fakeSuccessfulGeocode();

        $first = Geocode::address('1 Infinite Loop');
        $second = Geocode::address('1 Infinite Loop');

        $this->assertInstanceOf(Result::class, $first);
        $this->assertInstanceOf(Result::class, $second);
        $this->assertSame($first->formattedAddress, $second->formattedAddress);
        Http::assertSentCount(1);
    }

    public function test_address_cache_miss_for_different_addresses(): void
    {
        $this->fakeSuccessfulGeocode();

        Geocode::address('1 Infinite Loop');
        Geocode::address('1600 Amphitheatre Parkway');

        Http::assertSentCount(2);
    }

    public function test_lat_lng_uses_cache_on_second_lookup(): void
    {
        $this->fakeSuccessfulGeocode();

        Geocode::latLng(37.331741, -122.0303329);
        Geocode::latLng(37.331741, -122.0303329);

        Http::assertSentCount(1);
    }

    public function test_lat_lng_cache_miss_for_different_coordinates(): void
    {
        $this->fakeSuccessfulGeocode();

        Geocode::latLng(37.331741, -122.0303329);
        Geocode::latLng(40.7637931, -73.9722014);

        Http::assertSentCount(2);
    }

    public function test_place_id_uses_cache_on_second_lookup(): void
    {
        $this->fakeSuccessfulGeocode();

        Geocode::placeId('ChIJdd4hrwug2EcRmSrV3Vo6llI');
        Geocode::placeId('ChIJdd4hrwug2EcRmSrV3Vo6llI');

        Http::assertSentCount(1);
    }

    public function test_place_id_cache_miss_for_different_ids(): void
    {
        $this->fakeSuccessfulGeocode();

        Geocode::placeId('ChIJdd4hrwug2EcRmSrV3Vo6llI');
        Geocode::placeId('ChIJN1t_tDeuEmsRUsoyG83frY4');

        Http::assertSentCount(2);
    }

    public function test_cache_disabled_makes_http_request_every_time(): void
    {
        config(['geocode.cache.enabled' => false]);

        $this->fakeSuccessfulGeocode();

        Geocode::address('1 Infinite Loop');
        Geocode::address('1 Infinite Loop');

        Http::assertSentCount(2);
    }
}

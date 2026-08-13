<?php

namespace Jcf\Geocode\Tests;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Jcf\Geocode\Facades\Geocode;
use Jcf\Geocode\GeocodeServiceProvider;
use Jcf\Geocode\Result;
use Orchestra\Testbench\TestCase;

class GeocodeBatchTest extends TestCase
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
        $app['config']->set('geocode.throttle.per_second', 0);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('cache.stores.array', [
            'driver' => 'array',
        ]);
        $app['config']->set('geocode.cache.enabled', false);
        $app['config']->set('geocode.cache.store', 'array');
        $app['config']->set('geocode.cache.ttl', 86400);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Cache::store('array')->clear();
    }

    private function fakeSuccessfulGeocodeResponse(string $formattedAddress = '1 Infinite Loop, Cupertino, CA 95014, USA'): array
    {
        return [
            'status' => 'OK',
            'results' => [[
                'formatted_address' => $formattedAddress,
                'geometry' => [
                    'location' => [
                        'lat' => 37.331741,
                        'lng' => -122.0303329,
                    ],
                    'location_type' => 'ROOFTOP',
                ],
                'address_components' => [],
            ]],
        ];
    }

    public function test_addresses_returns_results_via_http_pool(): void
    {
        Http::fake([
            'maps.googleapis.com/*' => Http::response($this->fakeSuccessfulGeocodeResponse('Address A'), 200),
        ]);

        $results = Geocode::addresses(['A', 'B', 'C']);

        $this->assertCount(3, $results);

        foreach ($results as $result) {
            $this->assertInstanceOf(Result::class, $result);
        }

        Http::assertSentCount(3);
    }

    public function test_lat_lngs_returns_results_via_http_pool(): void
    {
        Http::fake([
            'maps.googleapis.com/*' => Http::response($this->fakeSuccessfulGeocodeResponse(), 200),
        ]);

        $results = Geocode::latLngs([
            ['lat' => 37.331741, 'lng' => -122.0303329],
            ['lat' => 40.7637931, 'lng' => -73.9722014],
            ['lat' => 51.5074, 'lng' => -0.1278],
        ]);

        $this->assertCount(3, $results);

        foreach ($results as $result) {
            $this->assertInstanceOf(Result::class, $result);
        }

        Http::assertSentCount(3);
    }

    public function test_addresses_respects_throttle_between_batches(): void
    {
        config(['geocode.throttle.per_second' => 1]);

        Http::fake([
            'maps.googleapis.com/*' => Http::response($this->fakeSuccessfulGeocodeResponse(), 200),
        ]);

        $start = microtime(true);

        Geocode::addresses(['A', 'B', 'C']);

        $elapsed = microtime(true) - $start;

        $this->assertGreaterThanOrEqual(2.0, $elapsed);
        Http::assertSentCount(3);
    }

    public function test_addresses_uses_cache_on_batch_lookup(): void
    {
        config(['geocode.cache.enabled' => true]);

        Http::fake([
            'maps.googleapis.com/*' => Http::response($this->fakeSuccessfulGeocodeResponse(), 200),
        ]);

        $first = Geocode::addresses(['A', 'B', 'C']);
        $second = Geocode::addresses(['A', 'B', 'C']);

        $this->assertCount(3, $first);
        $this->assertCount(3, $second);

        foreach ($first as $index => $result) {
            $this->assertInstanceOf(Result::class, $result);
            $this->assertInstanceOf(Result::class, $second[$index]);
        }

        Http::assertSentCount(3);
    }

    public function test_addresses_batch_uses_cache_for_individually_cached_address(): void
    {
        config(['geocode.cache.enabled' => true]);

        Http::fake([
            'maps.googleapis.com/*' => Http::response($this->fakeSuccessfulGeocodeResponse(), 200),
        ]);

        Geocode::address('Cached Address');

        $results = Geocode::addresses(['Cached Address', 'New Address']);

        $this->assertCount(2, $results);
        $this->assertInstanceOf(Result::class, $results[0]);
        $this->assertInstanceOf(Result::class, $results[1]);

        Http::assertSentCount(2);
    }
}

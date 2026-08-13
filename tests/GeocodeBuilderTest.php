<?php

namespace Jcf\Geocode\Tests;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Jcf\Geocode\Events\GeocodingPerformed;
use Jcf\Geocode\Facades\Geocode;
use Jcf\Geocode\GeocodeServiceProvider;
use Jcf\Geocode\Result;
use Orchestra\Testbench\TestCase;

class GeocodeBuilderTest extends TestCase
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
    }

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    private function fakeSuccessfulGeocode(string $formattedAddress = 'Av. Paulista, São Paulo - SP, Brasil'): void
    {
        Http::fake([
            'maps.googleapis.com/*' => Http::response([
                'status' => 'OK',
                'results' => [[
                    'formatted_address' => $formattedAddress,
                    'geometry' => [
                        'location' => ['lat' => -23.561414, 'lng' => -46.655881],
                        'location_type' => 'GEOMETRIC_CENTER',
                    ],
                    'address_components' => [],
                ]],
            ], 200),
        ]);
    }

    public function test_query_builder_sends_region_language_and_address_params(): void
    {
        $this->fakeSuccessfulGeocode();

        $result = Geocode::query()
            ->region('br')
            ->language('pt-BR')
            ->address('Av Paulista');

        $this->assertInstanceOf(Result::class, $result);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'maps.googleapis.com/maps/api/geocode/json')
                && $request['address'] === 'Av Paulista'
                && $request['region'] === 'br'
                && $request['language'] === 'pt-BR'
                && $request['key'] === 'test-key';
        });
    }

    public function test_query_builder_sends_components_and_bounds_params(): void
    {
        $this->fakeSuccessfulGeocode();

        Geocode::query()
            ->components(['country' => 'BR'])
            ->bounds([
                'southwest' => ['lat' => -23.6, 'lng' => -46.7],
                'northeast' => ['lat' => -23.5, 'lng' => -46.6],
            ])
            ->address('Av Paulista');

        Http::assertSent(function ($request) {
            return $request['components'] === 'country:BR'
                && $request['bounds'] === '-23.6,-46.7|-23.5,-46.6';
        });
    }

    public function test_query_builder_lat_lng_forwards_accumulated_params(): void
    {
        Http::fake([
            'maps.googleapis.com/*' => Http::response([
                'status' => 'OK',
                'results' => [[
                    'formatted_address' => 'Av. Paulista, São Paulo - SP, Brasil',
                    'geometry' => [
                        'location' => ['lat' => -23.561414, 'lng' => -46.655881],
                        'location_type' => 'GEOMETRIC_CENTER',
                    ],
                    'address_components' => [],
                ]],
            ], 200),
        ]);

        Geocode::query()
            ->language('pt-BR')
            ->latLng(-23.561414, -46.655881);

        Http::assertSent(function ($request) {
            return $request['latlng'] === '-23.561414,-46.655881'
                && $request['language'] === 'pt-BR';
        });
    }

    public function test_query_builder_place_id_forwards_accumulated_params(): void
    {
        $this->fakeSuccessfulGeocode();

        Geocode::query()
            ->region('br')
            ->placeId('ChIJtest123');

        Http::assertSent(function ($request) {
            return $request['place_id'] === 'ChIJtest123'
                && $request['region'] === 'br';
        });
    }

    public function test_geocoding_performed_event_is_dispatched_on_lookup(): void
    {
        Event::fake([GeocodingPerformed::class]);
        $this->fakeSuccessfulGeocode();

        Geocode::query()
            ->region('br')
            ->language('pt-BR')
            ->address('Av Paulista');

        Event::assertDispatched(GeocodingPerformed::class, function (GeocodingPerformed $event) {
            return $event->params['address'] === 'Av Paulista'
                && $event->params['region'] === 'br'
                && $event->params['language'] === 'pt-BR'
                && $event->result instanceof Result
                && $event->status === 'OK'
                && $event->cached === false
                && $event->durationMs >= 0;
        });
    }

    public function test_geocoding_performed_event_is_dispatched_on_batch_lookup(): void
    {
        Event::fake([GeocodingPerformed::class]);

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

        Geocode::addresses(['Address A', 'Address B']);

        Event::assertDispatchedTimes(GeocodingPerformed::class, 2);
    }

    public function test_geocoding_performed_event_marks_cached_lookups(): void
    {
        config(['geocode.cache.enabled' => true]);
        $this->fakeSuccessfulGeocode();

        Geocode::address('Av Paulista');

        Event::fake([GeocodingPerformed::class]);

        Geocode::address('Av Paulista');

        Event::assertDispatched(GeocodingPerformed::class, function (GeocodingPerformed $event) {
            return $event->cached === true
                && $event->durationMs === 0
                && $event->status === 'OK';
        });
    }

    public function test_log_debug_records_url_duration_and_status(): void
    {
        Log::spy();
        $this->fakeSuccessfulGeocode();

        Geocode::query()
            ->region('br')
            ->address('Av Paulista');

        Log::shouldHaveReceived('debug')
            ->once()
            ->with('Geocoding performed', \Mockery::on(function (array $context): bool {
                return ($context['url'] ?? '') === 'https://maps.googleapis.com/maps/api/geocode/json'
                    && ($context['params']['address'] ?? null) === 'Av Paulista'
                    && ($context['params']['region'] ?? null) === 'br'
                    && ($context['status'] ?? null) === 'OK'
                    && array_key_exists('duration_ms', $context)
                    && ($context['cached'] ?? null) === false;
            }));
    }

    public function test_query_builder_uses_cache_on_second_lookup(): void
    {
        config(['geocode.cache.enabled' => true]);
        $this->fakeSuccessfulGeocode();

        Geocode::query()->region('br')->address('Av Paulista');
        Geocode::query()->region('br')->address('Av Paulista');

        Http::assertSentCount(1);
    }
}

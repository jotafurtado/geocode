<?php

namespace Jcf\Geocode\Tests;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Jcf\Geocode\Exceptions\EmptyArgumentsException;
use Jcf\Geocode\Exceptions\GeocodingFailedException;
use Jcf\Geocode\Facades\Geocode;
use Jcf\Geocode\GeocodeServiceProvider;
use Jcf\Geocode\Result;
use Orchestra\Testbench\TestCase;

class GeocodeTest extends TestCase
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

    public function test_address_returns_result_for_successful_geocoding(): void
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
                    'address_components' => [[
                        'long_name' => '95014',
                        'short_name' => '95014',
                        'types' => ['postal_code'],
                    ]],
                ]],
            ], 200),
        ]);

        $result = Geocode::address('1 Infinite Loop');

        $this->assertInstanceOf(Result::class, $result);
        $this->assertSame(37.331741, $result->latitude);
        $this->assertSame(-122.0303329, $result->longitude);
        $this->assertSame('1 Infinite Loop, Cupertino, CA 95014, USA', $result->formattedAddress);
        $this->assertSame('ROOFTOP', $result->locationType);
        $this->assertSame('95014', $result->postalCode);
        $this->assertSame('95014', $result->raw->address_components[0]->long_name);
    }

    private function fakeSuccessfulGeocode(): void
    {
        Http::fake([
            'maps.googleapis.com/*' => Http::response([
                'status' => 'OK',
                'results' => [[
                    'formatted_address' => '767 5th Avenue, New York, NY 10153, USA',
                    'geometry' => [
                        'location' => [
                            'lat' => 40.7637931,
                            'lng' => -73.9722014,
                        ],
                        'location_type' => 'ROOFTOP',
                    ],
                    'address_components' => [[
                        'long_name' => '10153',
                        'short_name' => '10153',
                        'types' => ['postal_code'],
                    ]],
                ]],
            ], 200),
        ]);
    }

    public function test_lat_lng_returns_result_for_successful_reverse_geocoding(): void
    {
        $this->fakeSuccessfulGeocode();

        $result = Geocode::latLng(40.7637931, -73.9722014);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertSame(40.7637931, $result->latitude);
        $this->assertSame(-73.9722014, $result->longitude);
        $this->assertSame('767 5th Avenue, New York, NY 10153, USA', $result->formattedAddress);
    }

    public function test_place_id_returns_result_for_successful_lookup(): void
    {
        $this->fakeSuccessfulGeocode();

        $result = Geocode::placeId('ChIJdd4hrwug2EcRmSrV3Vo6llI');

        $this->assertInstanceOf(Result::class, $result);
        $this->assertSame('ROOFTOP', $result->locationType);
        $this->assertSame('10153', $result->postalCode);
    }

    /**
     * @dataProvider nonOkStatuses
     */
    public function test_non_ok_google_status_returns_null(string $status): void
    {
        Http::fake([
            'maps.googleapis.com/*' => Http::response([
                'status' => $status,
                'results' => [],
            ], 200),
        ]);

        $this->assertNull(Geocode::address('nowhere'));
        $this->assertNull(Geocode::latLng(0, 0));
        $this->assertNull(Geocode::placeId('invalid'));
    }

    public static function nonOkStatuses(): array
    {
        return [
            ['ZERO_RESULTS'],
            ['REQUEST_DENIED'],
            ['OVER_QUERY_LIMIT'],
            ['INVALID_REQUEST'],
            ['UNKNOWN_ERROR'],
        ];
    }

    public function test_non_successful_http_response_throws_geocoding_failed_exception(): void
    {
        Http::fake([
            'maps.googleapis.com/*' => Http::response(['error' => 'boom'], 500),
        ]);

        $this->expectException(GeocodingFailedException::class);

        Geocode::address('1 Infinite Loop');
    }

    public function test_connection_failure_throws_geocoding_failed_exception(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        $this->expectException(GeocodingFailedException::class);

        Geocode::address('1 Infinite Loop');
    }

    public function test_address_forwards_custom_params_and_config(): void
    {
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

        $result = Geocode::address('1 Infinite Loop', ['components' => 'country:US']);

        $this->assertInstanceOf(Result::class, $result);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'maps.googleapis.com/maps/api/geocode/json')
                && $request['address'] === '1 Infinite Loop'
                && $request['components'] === 'country:US'
                && $request['key'] === 'test-key'
                && $request['language'] === 'en';
        });
    }

    public function test_empty_address_throws_empty_arguments_exception(): void
    {
        $this->expectException(EmptyArgumentsException::class);

        Geocode::address('');
    }

    public function test_http_failures_are_retried_before_throwing(): void
    {
        Http::fake([
            'maps.googleapis.com/*' => Http::response(['error' => 'boom'], 500),
        ]);

        try {
            Geocode::address('1 Infinite Loop');
            $this->fail('Expected GeocodingFailedException');
        } catch (GeocodingFailedException $exception) {
            // Http::retry(2, ...) = two attempts total
            Http::assertSentCount(2);
        }
    }

    public function test_http_uses_configured_timeout_and_retry(): void
    {
        config([
            'geocode.timeout' => 5,
            'geocode.retry.times' => 1,
            'geocode.retry.sleep' => 200,
        ]);

        Http::fake([
            'maps.googleapis.com/*' => Http::response(['error' => 'boom'], 500),
        ]);

        try {
            (new \Jcf\Geocode\Geocode)->address('1 Infinite Loop');
            $this->fail('Expected GeocodingFailedException');
        } catch (GeocodingFailedException $exception) {
            Http::assertSentCount(1);
        }
    }

    public function test_logs_warning_when_api_key_empty_in_production(): void
    {
        config([
            'app.env' => 'production',
            'geocode.api_key' => '',
        ]);

        $this->app->detectEnvironment(fn (): string => 'production');

        Log::shouldReceive('warning')
            ->once()
            ->with('Google Geocoding API key is not configured.');

        Log::shouldReceive('debug')->once();

        $this->app->forgetInstance('geocode');
        $this->app->forgetInstance(\Jcf\Geocode\Geocode::class);

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
    }

    public function test_result_exposes_rich_google_fields_when_present(): void
    {
        Http::fake([
            'maps.googleapis.com/*' => Http::response([
                'status' => 'OK',
                'results' => [[
                    'place_id' => 'ChIJN1t_tDeuEmsRUsoyG83frY4',
                    'types' => ['street_address', 'subpremise'],
                    'partial_match' => true,
                    'plus_code' => [
                        'compound_code' => 'GRV6+52 Sydney, New South Wales, Australia',
                        'global_code' => '4RRGRV6+52',
                    ],
                    'formatted_address' => '48 Pirrama Rd, Pyrmont NSW 2009, Australia',
                    'geometry' => [
                        'location' => [
                            'lat' => -33.866489,
                            'lng' => 151.1958561,
                        ],
                        'location_type' => 'ROOFTOP',
                        'viewport' => [
                            'northeast' => [
                                'lat' => -33.8652389802915,
                                'lng' => 151.1971678802915,
                            ],
                            'southwest' => [
                                'lat' => -33.8679360197085,
                                'lng' => 151.1944699197085,
                            ],
                        ],
                        'bounds' => [
                            'northeast' => [
                                'lat' => -33.8652389802915,
                                'lng' => 151.1971678802915,
                            ],
                            'southwest' => [
                                'lat' => -33.8679360197085,
                                'lng' => 151.1944699197085,
                            ],
                        ],
                    ],
                    'address_components' => [],
                ]],
            ], 200),
        ]);

        $result = Geocode::address('48 Pirrama Rd, Pyrmont NSW 2009, Australia');

        $this->assertInstanceOf(Result::class, $result);
        $this->assertSame('ChIJN1t_tDeuEmsRUsoyG83frY4', $result->placeId);
        $this->assertSame(['street_address', 'subpremise'], $result->types);
        $this->assertTrue($result->partialMatch);
        $this->assertSame('GRV6+52 Sydney, New South Wales, Australia', $result->plusCode->compound_code);
        $this->assertSame('4RRGRV6+52', $result->plusCode->global_code);
        $this->assertSame(-33.8652389802915, $result->viewport->northeast->lat);
        $this->assertSame(151.1944699197085, $result->viewport->southwest->lng);
        $this->assertSame(-33.8652389802915, $result->bounds->northeast->lat);
        $this->assertSame(151.1944699197085, $result->bounds->southwest->lng);
    }

    public function test_result_exposes_null_for_absent_rich_fields(): void
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

        $result = Geocode::address('1 Infinite Loop');

        $this->assertInstanceOf(Result::class, $result);
        $this->assertNull($result->placeId);
        $this->assertNull($result->types);
        $this->assertNull($result->viewport);
        $this->assertNull($result->bounds);
        $this->assertNull($result->partialMatch);
        $this->assertNull($result->plusCode);
    }
}

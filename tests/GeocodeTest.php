<?php

namespace Jcf\Geocode\Tests;

use Illuminate\Support\Facades\Http;
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

        $this->expectException(\Jcf\Geocode\Exceptions\GeocodingFailedException::class);

        Geocode::address('1 Infinite Loop');
    }

    public function test_connection_failure_throws_geocoding_failed_exception(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
        });

        $this->expectException(\Jcf\Geocode\Exceptions\GeocodingFailedException::class);

        Geocode::address('1 Infinite Loop');
    }
}

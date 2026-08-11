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
}

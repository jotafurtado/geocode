<?php

namespace Jcf\Geocode;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Jcf\Geocode\Exceptions\EmptyArgumentsException;
use Jcf\Geocode\Exceptions\GeocodingFailedException;

class Geocode
{
    protected string $apiKey;

    protected string $language;

    public function __construct(?string $apiKey = null, ?string $language = null)
    {
        $this->apiKey = $apiKey ?? (string) config('geocode.api_key', '');
        $this->language = $language ?? (string) config('geocode.language', '');

        if (config('geocode.api_key') === '' && app()->environment('production')) {
            Log::warning('Google Geocoding API key is not configured.');
        }
    }

    public function address(string $address, array $params = []): ?Result
    {
        if ($address === '') {
            throw new EmptyArgumentsException('Empty arguments.');
        }

        return $this->lookup(array_merge($params, ['address' => $address]));
    }

    public function latLng(float|string $lat, float|string $lng): ?Result
    {
        if ($lat === '' || $lng === '') {
            throw new EmptyArgumentsException('Empty arguments.');
        }

        return $this->lookup(['latlng' => $lat.','.$lng]);
    }

    public function placeId(string $id): ?Result
    {
        if ($id === '') {
            throw new EmptyArgumentsException('Empty arguments.');
        }

        return $this->lookup(['place_id' => $id]);
    }

    protected function lookup(array $params): ?Result
    {
        if ($this->apiKey !== '') {
            $params['key'] = $this->apiKey;
        }

        if ($this->language !== '') {
            $params['language'] = $this->language;
        }

        try {
            $response = Http::timeout((int) config('geocode.timeout', 10))
                ->retry(
                    (int) config('geocode.retry.times', 2),
                    (int) config('geocode.retry.sleep', 100)
                )
                ->get('https://maps.googleapis.com/maps/api/geocode/json', $params);
        } catch (ConnectionException|RequestException $exception) {
            throw new GeocodingFailedException($exception->getMessage(), 0, $exception);
        }

        if (! $response->successful()) {
            throw new GeocodingFailedException(
                'Geocoding HTTP request failed with status '.$response->status()
            );
        }

        $payload = $response->object();

        return match ($payload->status ?? null) {
            'OK' => new Result($payload),
            'ZERO_RESULTS',
            'OVER_QUERY_LIMIT',
            'REQUEST_DENIED',
            'INVALID_REQUEST',
            'UNKNOWN_ERROR' => null,
            default => null,
        };
    }
}

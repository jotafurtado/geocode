<?php

namespace Jcf\Geocode;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Jcf\Geocode\Events\GeocodingPerformed;
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

    public function query(): GeocodeQueryBuilder
    {
        return new GeocodeQueryBuilder($this);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function address(string $address, array $params = []): ?Result
    {
        if ($address === '') {
            throw new EmptyArgumentsException('Empty arguments.');
        }

        return $this->lookup(array_merge($params, ['address' => $address]));
    }

    /**
     * @param  array<int, string>  $addresses
     * @param  array<string, mixed>  $params
     * @return array<int, Result|null>
     */
    public function addresses(array $addresses, array $params = []): array
    {
        $lookups = [];

        foreach ($addresses as $address) {
            if ($address === '') {
                throw new EmptyArgumentsException('Empty arguments.');
            }

            $lookups[] = array_merge($params, ['address' => $address]);
        }

        return $this->batchLookup($lookups);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function latLng(float|string $lat, float|string $lng, array $params = []): ?Result
    {
        if ($lat === '' || $lng === '') {
            throw new EmptyArgumentsException('Empty arguments.');
        }

        return $this->lookup(array_merge($params, ['latlng' => $lat.','.$lng]));
    }

    /**
     * @param  array<int, array{lat?: float|string|null, lng?: float|string|null}>  $coords
     * @return array<int, Result|null>
     */
    public function latLngs(array $coords): array
    {
        $lookups = [];

        foreach ($coords as $coord) {
            $lat = $coord['lat'] ?? null;
            $lng = $coord['lng'] ?? null;

            if ($lat === null || $lng === null || $lat === '' || $lng === '') {
                throw new EmptyArgumentsException('Empty arguments.');
            }

            $lookups[] = ['latlng' => $lat.','.$lng];
        }

        return $this->batchLookup($lookups);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function placeId(string $id, array $params = []): ?Result
    {
        if ($id === '') {
            throw new EmptyArgumentsException('Empty arguments.');
        }

        return $this->lookup(array_merge($params, ['place_id' => $id]));
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected function lookup(array $params): ?Result
    {
        $params = $this->prepareParams($params);

        if (! config('geocode.cache.enabled', false)) {
            return $this->performLookup($params);
        }

        $cache = Cache::store(config('geocode.cache.store'));
        $key = $this->cacheKey($params);

        if ($cache->has($key)) {
            /** @var Result|null $result */
            $result = $cache->get($key);
            $this->recordGeocoding($params, $result, 0, $this->cachedStatus($result), true);

            return $result;
        }

        $result = $this->performLookup($params);

        $cache->put($key, $result, (int) config('geocode.cache.ttl', 86400));

        return $result;
    }

    /**
     * @param  array<int, array<string, mixed>>  $paramsList
     * @return array<int, Result|null>
     */
    protected function batchLookup(array $paramsList): array
    {
        if ($paramsList === []) {
            return [];
        }

        $results = array_fill(0, count($paramsList), null);
        $pending = [];

        foreach ($paramsList as $index => $params) {
            $params = $this->prepareParams($params);

            if (config('geocode.cache.enabled', false)) {
                $cache = Cache::store(config('geocode.cache.store'));
                $key = $this->cacheKey($params);

                if ($cache->has($key)) {
                    /** @var Result|null $cachedResult */
                    $cachedResult = $cache->get($key);
                    $results[$index] = $cachedResult;
                    $this->recordGeocoding($params, $cachedResult, 0, $this->cachedStatus($cachedResult), true);

                    continue;
                }
            }

            $pending[$index] = $params;
        }

        if ($pending === []) {
            return $results;
        }

        $fetched = $this->performBatchLookup($pending);

        foreach ($fetched as $index => $result) {
            $results[$index] = $result;

            if (config('geocode.cache.enabled', false)) {
                Cache::store(config('geocode.cache.store'))->put(
                    $this->cacheKey($pending[$index]),
                    $result,
                    (int) config('geocode.cache.ttl', 86400),
                );
            }
        }

        return $results;
    }

    /**
     * @param  array<int, array<string, mixed>>  $indexedParams
     * @return array<int, Result|null>
     */
    protected function performBatchLookup(array $indexedParams): array
    {
        $throttle = (int) config('geocode.throttle.per_second', 0);

        if ($throttle <= 0) {
            return $this->poolLookup($indexedParams);
        }

        $results = [];
        $chunks = array_chunk($indexedParams, $throttle, true);
        $firstChunk = true;

        foreach ($chunks as $chunk) {
            if (! $firstChunk) {
                sleep(1);
            }

            $firstChunk = false;
            $results += $this->poolLookup($chunk);
        }

        return $results;
    }

    /**
     * @param  array<int, array<string, mixed>>  $indexedParams
     * @return array<int, Result|null>
     */
    protected function poolLookup(array $indexedParams): array
    {
        $url = $this->geocodeUrl();

        try {
            $responses = Http::pool(function (Pool $pool) use ($indexedParams, $url): array {
                $requests = [];

                foreach ($indexedParams as $index => $params) {
                    $requests[(string) $index] = $this->pendingRequest($pool->as((string) $index))
                        ->get($url, $this->addApiKey($params));
                }

                return $requests;
            });
        } catch (ConnectionException|RequestException $exception) {
            throw new GeocodingFailedException($exception->getMessage(), 0, $exception);
        }

        $results = [];

        foreach ($indexedParams as $index => $params) {
            $response = $responses[(string) $index] ?? null;

            if (! $response instanceof Response) {
                throw new GeocodingFailedException('Geocoding HTTP pool request failed.');
            }

            if ($response->failed()) {
                throw new GeocodingFailedException(
                    'Geocoding HTTP request failed with status '.$response->status()
                );
            }

            [$result, $status] = $this->parseResponseWithStatus($response);
            $results[$index] = $result;
            $this->recordGeocoding($params, $result, 0, $status, false);
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected function performLookup(array $params): ?Result
    {
        $url = $this->geocodeUrl();
        $startedAt = microtime(true);

        try {
            $response = $this->pendingRequest(Http::timeout((int) config('geocode.timeout', 10)))
                ->get($url, $this->addApiKey($params));
        } catch (ConnectionException|RequestException $exception) {
            throw new GeocodingFailedException($exception->getMessage(), 0, $exception);
        }

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        if (! $response->successful()) {
            throw new GeocodingFailedException(
                'Geocoding HTTP request failed with status '.$response->status()
            );
        }

        [$result, $status] = $this->parseResponseWithStatus($response);
        $this->recordGeocoding($params, $result, $durationMs, $status, false);

        return $result;
    }

    protected function pendingRequest(mixed $request): mixed
    {
        return $request
            ->timeout((int) config('geocode.timeout', 10))
            ->retry(
                (int) config('geocode.retry.times', 2),
                (int) config('geocode.retry.sleep', 100)
            );
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    protected function prepareParams(array $params): array
    {
        if ($this->language !== '' && ! isset($params['language'])) {
            $params['language'] = $this->language;
        }

        return $params;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    protected function addApiKey(array $params): array
    {
        if ($this->apiKey !== '') {
            $params['key'] = $this->apiKey;
        }

        return $params;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected function cacheKey(array $params): string
    {
        return 'geocode:'.md5(json_encode([$params, $this->language]) ?: '');
    }

    /**
     * @return array{0: ?Result, 1: string}
     */
    protected function parseResponseWithStatus(Response $response): array
    {
        $payload = $response->object();

        if (! is_object($payload)) {
            return [null, 'UNKNOWN'];
        }

        $status = (string) ($payload->status ?? 'UNKNOWN');

        $result = match ($status) {
            'OK' => new Result($payload),
            'ZERO_RESULTS',
            'OVER_QUERY_LIMIT',
            'REQUEST_DENIED',
            'INVALID_REQUEST',
            'UNKNOWN_ERROR' => null,
            default => null,
        };

        return [$result, $status];
    }

    protected function parseResponse(Response $response): ?Result
    {
        [$result] = $this->parseResponseWithStatus($response);

        return $result;
    }

    protected function geocodeUrl(): string
    {
        return 'https://maps.googleapis.com/maps/api/geocode/json';
    }

    protected function cachedStatus(?Result $result): string
    {
        return $result !== null ? 'OK' : 'ZERO_RESULTS';
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected function recordGeocoding(array $params, ?Result $result, int $durationMs, string $status, bool $cached): void
    {
        Event::dispatch(new GeocodingPerformed($params, $result, $durationMs, $status, $cached));

        Log::debug('Geocoding performed', [
            'url' => $this->geocodeUrl(),
            'params' => $params,
            'duration_ms' => $durationMs,
            'status' => $status,
            'cached' => $cached,
        ]);
    }
}

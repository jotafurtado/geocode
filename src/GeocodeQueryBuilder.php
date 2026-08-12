<?php

namespace Jcf\Geocode;

class GeocodeQueryBuilder
{
    /** @var array<string, mixed> */
    protected array $params = [];

    public function __construct(protected Geocode $geocode) {}

    public function region(string $region): self
    {
        $this->params['region'] = $region;

        return $this;
    }

    public function language(string $language): self
    {
        $this->params['language'] = $language;

        return $this;
    }

    /**
     * @param  array<int|string, string>  $components
     */
    public function components(array $components): self
    {
        $this->params['components'] = $this->formatComponents($components);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $bounds
     */
    public function bounds(array $bounds): self
    {
        $this->params['bounds'] = $this->formatBounds($bounds);

        return $this;
    }

    public function address(string $address): ?Result
    {
        return $this->geocode->address($address, $this->params);
    }

    public function latLng(float|string $lat, float|string $lng): ?Result
    {
        return $this->geocode->latLng($lat, $lng, $this->params);
    }

    public function placeId(string $id): ?Result
    {
        return $this->geocode->placeId($id, $this->params);
    }

    /**
     * @param  array<int|string, string>  $components
     */
    protected function formatComponents(array $components): string
    {
        $parts = [];

        foreach ($components as $key => $value) {
            $parts[] = is_int($key) ? $value : $key.':'.$value;
        }

        return implode('|', $parts);
    }

    /**
     * @param  array<string, mixed>  $bounds
     */
    protected function formatBounds(array $bounds): string
    {
        if (isset($bounds['southwest'], $bounds['northeast'])) {
            /** @var array{lat: float|string, lng: float|string} $southwest */
            $southwest = $bounds['southwest'];
            /** @var array{lat: float|string, lng: float|string} $northeast */
            $northeast = $bounds['northeast'];

            return $southwest['lat'].','.$southwest['lng'].'|'.$northeast['lat'].','.$northeast['lng'];
        }

        return (string) ($bounds['value'] ?? '');
    }
}

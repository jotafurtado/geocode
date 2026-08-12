<?php

namespace Jcf\Geocode;

class Result
{
    public readonly float $latitude;

    public readonly float $longitude;

    public readonly string $formattedAddress;

    public readonly string $locationType;

    public readonly ?string $postalCode;

    public readonly ?string $placeId;

    /** @var array<int, string>|null */
    public readonly ?array $types;

    public readonly ?object $viewport;

    public readonly ?object $bounds;

    public readonly ?bool $partialMatch;

    public readonly ?object $plusCode;

    public readonly object $raw;

    public function __construct(object $response)
    {
        $result = $response->results[0];

        $this->raw = $result;
        $this->formattedAddress = $result->formatted_address;
        $this->latitude = $result->geometry->location->lat;
        $this->longitude = $result->geometry->location->lng;
        $this->locationType = $result->geometry->location_type;
        $this->postalCode = $this->extractPostalCode($result);
        $this->placeId = $result->place_id ?? null;
        $this->types = isset($result->types) ? (array) $result->types : null;
        $this->viewport = $result->geometry->viewport ?? null;
        $this->bounds = $result->geometry->bounds ?? null;
        $this->partialMatch = $result->partial_match ?? null;
        $this->plusCode = $result->plus_code ?? null;
    }

    private function extractPostalCode(object $result): ?string
    {
        foreach ($result->address_components ?? [] as $component) {
            if (isset($component->types) && in_array('postal_code', (array) $component->types, true)) {
                return $component->long_name;
            }
        }

        return null;
    }
}

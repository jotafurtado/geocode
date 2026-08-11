<?php

namespace Jcf\Geocode;

class Result
{
    public float $latitude;

    public float $longitude;

    public string $formattedAddress;

    public string $locationType;

    public string|false $postalCode;

    public object $raw;

    public function __construct(object $response)
    {
        $result = $response->results[0];

        $this->raw = $result;
        $this->formattedAddress = $result->formatted_address;
        $this->latitude = $result->geometry->location->lat;
        $this->longitude = $result->geometry->location->lng;
        $this->locationType = $result->geometry->location_type;
        $this->postalCode = $this->extractPostalCode($result);
    }

    private function extractPostalCode(object $result): string|false
    {
        foreach ($result->address_components ?? [] as $component) {
            if (isset($component->types) && in_array('postal_code', (array) $component->types, true)) {
                return $component->long_name;
            }
        }

        return false;
    }
}

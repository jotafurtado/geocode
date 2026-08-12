<?php

namespace Jcf\Geocode\Events;

use Jcf\Geocode\Result;

class GeocodingPerformed
{
    /**
     * @param  array<string, mixed>  $params
     */
    public function __construct(
        public readonly array $params,
        public readonly ?Result $result,
        public readonly int $durationMs,
        public readonly string $status,
        public readonly bool $cached,
    ) {}
}

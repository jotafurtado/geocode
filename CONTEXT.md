# Google Geocoding for Laravel

A thin Laravel service provider that wraps the Google Maps Geocoding API, turning addresses, coordinates, and Place IDs into geographic results.

## Language

**Geocoding**:
Resolving a free-text address into coordinates (latitude/longitude).
_Avoid_: forward lookup, address search

**Reverse geocoding**:
Resolving coordinates (latitude/longitude) into a human-readable address.
_Avoid_: reverse lookup, lat/lng lookup

**Place ID lookup**:
Resolving a Google Place ID into its geographic details.
_Avoid_: place lookup, POI lookup

**Result**:
The geographic data returned for a successfully resolved query (latitude, longitude, formatted address, location type, address components).
_Avoid_: Response, payload, answer, object

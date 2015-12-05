<?php 
namespace Jcf\Geocode;

use \GuzzleHttp\Client;

class Geocode
{

    public static function make()
    {
        return new static();
    }

    public function address($address)
    {

        if (empty($address)) {
            throw new Exceptions\EmptyArgumentsException('Empty arguments.');
        }
	$client = new \GuzzleHttp\Client();
	$response = $client->request('GET', 'http://maps.googleapis.com/maps/api/geocode/json', [
	    'query' => ['address' => $address]
	]);
        
        # check for status in the response
		switch( $response->json()['status'] )
		{
			
			case "ZERO_RESULTS": # indicates that the geocode was successful but returned no results. This may occur if the geocoder was passed a non-existent address.
			case "OVER_QUERY_LIMIT": # indicates that you are over your quota.
			case "REQUEST_DENIED": # indicates that your request was denied.
			case "INVALID_REQUEST": # generally indicates that the query (address, components or latlng) is missing.
			case "UNKNOWN_ERROR":
				return false;
				
			case "OK": # indicates that no errors occurred; the address was successfully parsed and at least one geocode was returned.
				return new Response($response->json());
		}

    }

    public function latLng($lat, $lng)
    {

    	if (empty($lat) || empty($lng)) {
    		throw new Exceptions\EmptyArgumentsException('Empty arguments.');
    	}

        $response = \GuzzleHttp\get('http://maps.googleapis.com/maps/api/geocode/json', [
            'query' => ['latlng' => $lat . ',' . $lng]
		]);
        
        # check for status in the response
		switch( $response->json()['status'] )
		{
			
			case "ZERO_RESULTS": # indicates that the geocode was successful but returned no results. This may occur if the geocoder was passed a non-existent address.
			case "OVER_QUERY_LIMIT": # indicates that you are over your quota.
			case "REQUEST_DENIED": # indicates that your request was denied.
			case "INVALID_REQUEST": # generally indicates that the query (address, components or latlng) is missing.
			case "UNKNOWN_ERROR":
				return false;
				
			case "OK": # indicates that no errors occurred; the address was successfully parsed and at least one geocode was returned.
				return new Response($response->json());
		}

    }

}

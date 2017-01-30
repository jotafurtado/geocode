<?php 
namespace Jcf\Geocode;

use \GuzzleHttp\Client;
use Config;

class Geocode
{
    protected $apiKey;
    protected $language;

    public function __construct()
    {
        $this->apiKey = config('geocode.apikey');
        $this->language = config('geocode.language');
    }

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
        $params = ['address' => $address];
        if (!empty($this->apiKey)) {
            $params['key'] = $this->apiKey;
        }
        if (!empty($this->language)) {
            $params['language'] = $this->language;
        }
		$response = json_decode($client->get('https://maps.googleapis.com/maps/api/geocode/json', [
		    'query' => $params
		])->getBody());

        # check for status in the response
		switch( $response->status )
		{
			
			case "ZERO_RESULTS": # indicates that the geocode was successful but returned no results. This may occur if the geocoder was passed a non-existent address.
			case "OVER_QUERY_LIMIT": # indicates that you are over your quota.
			case "REQUEST_DENIED": # indicates that your request was denied.
			case "INVALID_REQUEST": # generally indicates that the query (address, components or latlng) is missing.
			case "UNKNOWN_ERROR":
				return false;
				
			case "OK": # indicates that no errors occurred; the address was successfully parsed and at least one geocode was returned.
				return new Response($response);
		}

    }

    public function latLng($lat, $lng)
    {

    	if (empty($lat) || empty($lng)) {
    		throw new Exceptions\EmptyArgumentsException('Empty arguments.');
    	}

        $params = ['latlng' => $lat . ',' . $lng];
        if (!empty($this->apiKey)) {
            $params['key'] = $this->apiKey;
        }
        if (!empty($this->language)) {
            $params['language'] = $this->language;
        }

        $client = new \GuzzleHttp\Client();
        $response = json_decode($client->get('https://maps.googleapis.com/maps/api/geocode/json', [
            'query' => $params
        ])->getBody());

        # check for status in the response
		switch( $response->status )
		{
			
			case "ZERO_RESULTS": # indicates that the geocode was successful but returned no results. This may occur if the geocoder was passed a non-existent address.
			case "OVER_QUERY_LIMIT": # indicates that you are over your quota.
			case "REQUEST_DENIED": # indicates that your request was denied.
			case "INVALID_REQUEST": # generally indicates that the query (address, components or latlng) is missing.
			case "UNKNOWN_ERROR":
				return false;
				
			case "OK": # indicates that no errors occurred; the address was successfully parsed and at least one geocode was returned.
				return new Response($response);
		}

    }
	
    public function placeId($id)
    {
        if (empty($id)) {
            throw new Exceptions\EmptyArgumentsException('Empty arguments.');
        }

        $params = ['place_id' => $id];

        if (!empty($this->apiKey)) {
            $params['key'] = $this->apiKey;
        }

        if (!empty($this->language)) {
            $params['language'] = $this->language;
        }

        $client = new \GuzzleHttp\Client();
        $response = json_decode($client->get('https://maps.googleapis.com/maps/api/geocode/json', [
            'query' => $params
        ])->getBody());

        # check for status in the response
        switch( $response->status )
        {

            case "ZERO_RESULTS": # indicates that the geocode was successful but returned no results. This may occur if the geocoder was passed a non-existent address.
            case "OVER_QUERY_LIMIT": # indicates that you are over your quota.
            case "REQUEST_DENIED": # indicates that your request was denied.
            case "INVALID_REQUEST": # generally indicates that the query (address, components or latlng) is missing.
            case "UNKNOWN_ERROR":
                return false;

            case "OK": # indicates that no errors occurred; the address was successfully parsed and at least one geocode was returned.
                return new Response($response);
        }
    }

}

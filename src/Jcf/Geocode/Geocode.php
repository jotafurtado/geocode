<?php 
namespace Jcf\Geocode;

use \GuzzleHttp\Client;

class Geocode
{
    
	////////////////////
	// Static methods //
	////////////////////

    protected static $GoogleApiKey = null;
    public static function make($apiKey = null) {
        return new static(!is_null($apiKey) ? $apiKey : static::$GoogleApiKey);
    }
    
    public static function setApiKey($apiKey) {
    	static::$GoogleApiKey = $apiKey;    	
    }


    /////////////////
    // Constructor //
    /////////////////

    protected $apiKey;
    public function __construct($apiKey = null) {
    	$this->setApiKey = $apiKey;
    }


    ////////////////////
    // Public methods //
    ////////////////////

    public function address($address) {

        if (empty($address)) {
            throw new Exceptions\EmptyArgumentsException('Empty arguments.');
        }
		$response = json_decode(\GuzzleHttp\get('http://maps.googleapis.com/maps/api/geocode/json', $this->requestParams([
		    'query' => ['address' => $address]
		]))->getBody());
		
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

    public function latLng($lat, $lng) {

    	if (empty($lat) || empty($lng)) {
    		throw new Exceptions\EmptyArgumentsException('Empty arguments.');
    	}

    	$response = json_decode(\GuzzleHttp\get('http://maps.googleapis.com/maps/api/geocode/json', $this->requestParams([
		    'query' => ['latlng' => $lat . ',' . $lng]
		]))->getBody());
        dd($response);
        
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


    /////////////////////////////
    // Internal helper methods //
    /////////////////////////////

    protected function requestParams($options = []) {

    	// Api key?
    	if (!is_null($this->apiKey)) {
    		$options['api_key'] = $this->apiKey;
    	}
    	return $options;

    }

}

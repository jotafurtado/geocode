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

        $response = \GuzzleHttp\get('http://maps.googleapis.com/maps/api/geocode/json', [
            'query' => ['address' => $address]
        ]);

        if ($response->json()['status'] == 'ZERO_RESULTS') {
            return false;
        }

        return new Response($response->json());

    }

    public function latLng($lat, $lng)
    {

    	if (empty($lat) || empty($lng)) {
    		throw new Exceptions\EmptyArgumentsException('Empty arguments.');
    	}

        $response = \GuzzleHttp\get('http://maps.googleapis.com/maps/api/geocode/json', [
            'query' => ['latlng' => $lat . ',' . $lng]
		]);

		if ($response->json()['status'] == 'ZERO_RESULTS') {
			return false;
		}

		return new Response($response->json());

    }

}
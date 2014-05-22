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

    	if(empty($address)){
    		throw new Exceptions\NoAddressPassedException("No address passed to geocode call.");
    	}

		$response = \GuzzleHttp\get('http://maps.googleapis.com/maps/api/geocode/json', array(
		    'query' => array('address' => $address)
		));

		if($response->json()['status'] == 'ZERO_RESULTS'){
			return false;
		}

		return new Address($response->json());

    }

}
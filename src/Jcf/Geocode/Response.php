<?php 
namespace Jcf\Geocode;

class Response
{
	public function __construct($response)
	{
		$this->response = $response->results[0];
	}

	public function raw()
	{
		return (object) $this->response;
	}

	public function formattedAddress()
	{
		return $this->response->formatted_address;
	}

	public function latitude()
	{
		return $this->response->geometry->location->lat;
	}

	public function longitude()
	{
		return $this->response->geometry->location->lng;
	}

	public function locationType()
	{
		return $this->response->geometry->location_type;
	}
	
	public function postalCode()
    	{
		foreach ($this->response->address_components as $component) {
		    if (isset($component->types) && in_array('postal_code', $component->types)) {
			return $component->long_name;
		    }
		}

        	return false;
    	}
}

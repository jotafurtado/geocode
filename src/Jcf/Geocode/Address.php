<?php 
namespace Jcf\Geocode;

class Address
{
	public function __construct($response)
	{
		$this->response = $response['results'][0];
	}

	public function formatted()
	{
		return $this->response['formatted_address'];
	}

	public function latitude()
	{
		return $this->response['geometry']['location']['lat'];
	}

	public function longitude()
	{
		return $this->response['geometry']['location']['lng'];
	}
}
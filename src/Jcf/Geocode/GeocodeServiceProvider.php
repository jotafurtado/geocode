<?php namespace Jcf\Geocode;

use Illuminate\Support\ServiceProvider;

class GeocodeServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        $this->app['geocode'] = $this->app->share(function($app)
        {
            return new \Jcf\Geocode\Geocode;
        });
        $this->app->booting(function()
        {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('Geocode', 'Jcf\Geocode\Facades\Geocode');
        });
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}

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
         * Bootstrap the configuration
         *
         * @return void
         */
        public function boot()
        {
            $source = dirname(__DIR__).'/../../config/geocode.php';
            if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
                $this->publishes([$source => config_path('geocode.php')]);
            } elseif ($this->app instanceof LumenApplication) {
                $this->app->configure('geocode');
            }
            $this->mergeConfigFrom($source, 'geocode');
        }

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
	    $this->app->singleton('geocode',function($app)
	    {
	        return new \Jcf\Geocode\Geocode;
	    });

	    if ($this->app instanceof LaravelApplication) {
	        $this->app->booting(function ()
	        {
		    $loader = \Illuminate\Foundation\AliasLoader::getInstance();
		    $loader->alias('Geocode', 'Jcf\Geocode\Facades\Geocode');
	        });
	    }
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

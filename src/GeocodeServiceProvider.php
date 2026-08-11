<?php

namespace Jcf\Geocode;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class GeocodeServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('geocode')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton('geocode', function () {
            return new Geocode;
        });

        $this->app->alias('geocode', Geocode::class);
    }
}

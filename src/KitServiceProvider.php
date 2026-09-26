<?php

declare(strict_types=1);

namespace Spaceworks\Kit;

use Illuminate\Support\ServiceProvider;
use Spaceworks\Kit\Seo\SiteSeoDefaults;

/**
 * Merges the kit's default "seo" and "attribution" config and registers the
 * site-wide Laravel Head defaults. Each site overrides the config keys it
 * needs in its own config/seo.php and config/attribution.php.
 */
class KitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/seo.php', 'seo');
        $this->mergeConfigFrom(__DIR__.'/../config/attribution.php', 'attribution');
    }

    public function boot(): void
    {
        $this->app->make(SiteSeoDefaults::class)->apply();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/seo.php' => config_path('seo.php'),
                __DIR__.'/../config/attribution.php' => config_path('attribution.php'),
            ], 'spaceworks-kit-config');
        }
    }
}

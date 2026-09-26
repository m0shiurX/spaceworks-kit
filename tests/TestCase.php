<?php

declare(strict_types=1);

namespace Spaceworks\Kit\Tests;

use Inertia\ServiceProvider as InertiaServiceProvider;
use Laravel\Head\HeadServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spaceworks\Kit\KitServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            InertiaServiceProvider::class,
            HeadServiceProvider::class,
            KitServiceProvider::class,
        ];
    }
}

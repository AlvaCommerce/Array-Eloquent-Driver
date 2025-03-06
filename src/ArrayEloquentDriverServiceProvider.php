<?php

namespace Alva\ArrayEloquentDriver;

use Alva\ArrayEloquentDriver\Database\Array\Connection as ArrayConnection;
use Illuminate\Database\Connection as ConnectionBase;
use Illuminate\Support\Facades\Config;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ArrayEloquentDriverServiceProvider extends PackageServiceProvider
{
    public static string $name = 'array-eloquent-driver';

    public static string $viewNamespace = 'array-eloquent-driver';

    public function registeringPackage()
    {
        ConnectionBase::resolverFor('array_eloquent_driver', static function ($connection, $database, $prefix, $config) {
            if (app()->has(ArrayConnection::class)) {
                return app(ArrayConnection::class);
            }

            return new ArrayConnection($connection, $database, $prefix, $config);
        });
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name);
    }

    public function packageBooted(): void
    {
        Config::set('database.connections.array_eloquent_driver', [
            'driver' => 'array_eloquent_driver',
            'database' => 'array_eloquent_driver',
        ]);
    }
}

<?php

namespace AdamDziuk\LaravelAgentCost\Tests;

use AdamDziuk\LaravelAgentCost\LaravelAgentCostServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LaravelAgentCostServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        // Pin the cache store explicitly so tests don't depend on
        // whatever the shared testbench skeleton's .env happens to
        // resolve `cache.default` to.
        $app['config']->set('cache.default', 'array');

        // The dashboard runs through the `web` middleware group, whose
        // cookie encryption needs an application key.
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));

        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}

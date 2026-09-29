<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Bootstrap\RegisterProviders;

abstract class IsolatedDatabaseTestCase extends TestCase
{
    public function createApplication()
    {
        $app = require dirname(__DIR__).'/bootstrap/app.php';
        // Never read the application's .env or permit a server DB connection in this suite.
        $app->loadEnvironmentFrom('.env.isolated-do-not-create');
        $app->beforeBootstrapping(RegisterProviders::class, function ($app) {
            $app['config']->set('database.default', 'sqlite');
            $app['config']->set('database.connections', ['sqlite' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
                'url' => null, 'foreign_key_constraints' => true,
            ]]);
            $app['config']->set('cache.default', 'array');
            $app['config']->set('session.driver', 'array');
            $app['config']->set('mail.default', 'array');
            $app['config']->set('queue.default', 'sync');
        });
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}

<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Refuse to run any test against a persistent application database.
     */
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        // Tests must not inherit database-backed infrastructure from .env.
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('app.maintenance.driver', 'cache');
        $app['config']->set('app.maintenance.store', 'array');

        $environment = $app->environment();
        $connection = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$connection}.database");

        if ($environment !== 'testing' || $connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(sprintf(
                'Unsafe test database configuration blocked (environment=%s, connection=%s, database=%s).',
                $environment,
                $connection,
                (string) $database,
            ));
        }

        return $app;
    }
}

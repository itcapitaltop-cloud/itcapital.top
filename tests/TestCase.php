<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        $this->ensureSafeTestingDatabase($app);

        return $app;
    }

    private function ensureSafeTestingDatabase(Application $app): void
    {
        if (! $app->environment('testing')) {
            throw new RuntimeException('Tests must run with APP_ENV=testing. Refusing to refresh a non-testing database.');
        }

        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if ($database === 'testing' || str_ends_with($database, '_test') || str_ends_with($database, '_testing')) {
            return;
        }

        throw new RuntimeException("Refusing to refresh unsafe database [{$database}] for tests.");
    }
}

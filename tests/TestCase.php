<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Runs after the application is booted but before the test traits set
     * themselves up, which is the window where RefreshDatabase migrates. That
     * ordering matters: checking earlier has no application to read config
     * from, checking later is already too late because the tables are dropped.
     *
     * @return array<int, string>
     */
    protected function setUpTraits(): array
    {
        $this->guardAgainstWipingADevelopmentDatabase();

        return parent::setUpTraits();
    }

    /**
     * Every test here uses RefreshDatabase, which runs migrate:fresh and
     * therefore destroys all data in the configured database.
     *
     * That is safe for sqlite and for a database explicitly named as a test
     * database. It is not safe for a development database, so refuse to run
     * against one unless the caller opts in deliberately.
     */
    protected function guardAgainstWipingADevelopmentDatabase(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'sqlite') {
            return;
        }

        $database = (string) config("database.connections.{$connection}.database");

        if (str_contains(strtolower($database), 'test')) {
            return;
        }

        // Explicit opt-out for setups without permission to create a separate
        // test database.
        if (filter_var(env('DB_WIPE_ALLOWED', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        throw new RuntimeException(
            "Refusing to run the test suite against the development database [{$database}]. "
            .'RefreshDatabase drops every table before each run. Point the suite at a database '
            .'whose name contains "test", install pdo_sqlite to use the default in-memory setup, '
            .'or set DB_WIPE_ALLOWED=true to confirm you accept losing this data.'
        );
    }
}

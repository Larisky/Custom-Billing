<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

trait CreatesApplication
{
    use RefreshDatabase;

    public function createApplication()
    {
        // PHPUnit's <env force="true"> only updates getenv()/$_ENV, but Laravel's
        // Env repository reads $_SERVER first — which still holds the Docker
        // container's real DB_* values. Propagate the forced overrides so the
        // sqlite/:memory: connection from phpunit.xml actually takes effect.
        foreach (['DB_CONNECTION', 'DB_DATABASE'] as $key) {
            if (($value = getenv($key)) !== false) {
                $_SERVER[$key] = $value;
            }
        }

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }
}

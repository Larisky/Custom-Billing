<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

trait CreatesApplication
{
    use RefreshDatabase;

   
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }
}

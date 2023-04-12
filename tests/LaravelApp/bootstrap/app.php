<?php

use Eloquentity\Tests\LaravelApp\ConsoleKernel;

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    ConsoleKernel::class
);

return $app;

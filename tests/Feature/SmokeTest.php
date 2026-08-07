<?php

it('boots the application and reaches the database', function () {
    expect(\Illuminate\Support\Facades\DB::connection()->getDriverName())->toBe('pgsql');
    expect(\Illuminate\Support\Facades\DB::connection()->getPdo())->not->toBeNull();
});

it('is isolated from the development database', function () {
    $database = \Illuminate\Support\Facades\DB::connection()->getDatabaseName();

    expect($database)->toBe('niue_doe_test');
    expect($database)->not->toBe('niue_doe');
});

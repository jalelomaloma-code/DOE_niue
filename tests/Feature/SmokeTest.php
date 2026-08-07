<?php

it('boots the application and reaches the database', function () {
    expect(\Illuminate\Support\Facades\DB::connection()->getDriverName())->toBe('pgsql');
    expect(\Illuminate\Support\Facades\DB::connection()->getPdo())->not->toBeNull();
});

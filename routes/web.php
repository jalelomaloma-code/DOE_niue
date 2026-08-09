<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Http\Controllers\HomeController::class, 'index'])->name('home');

/*
 * Page catch-all. MUST be registered last — it matches any path.
 * /admin, /storage and /livewire are excluded by pattern; reserved slugs
 * are additionally rejected at CMS save time (Page::RESERVED_SLUGS).
 */
Route::get('/{path}', [\App\Http\Controllers\PageController::class, 'show'])
    ->where('path', '^(?!admin|storage|livewire)[a-z0-9\-]+(\/[a-z0-9\-]+)?$')
    ->name('pages.show');

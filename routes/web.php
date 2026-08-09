<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Http\Controllers\HomeController::class, 'index'])->name('home');

/*
 * Explicit programme detail route. Three path segments — beyond what the
 * Page catch-all's regex permits (one or two) — so it can never collide
 * with a Page, but it still needs to be registered ahead of the catch-all
 * like every other explicit route.
 */
Route::get('/our-work/environment-programmes/{slug}', [\App\Http\Controllers\ProgrammeController::class, 'show'])
    ->name('programmes.show');

/*
 * Page catch-all. MUST be registered last — it matches any path.
 * /admin, /storage and /livewire are excluded by pattern; reserved slugs
 * are additionally rejected at CMS save time (Page::RESERVED_SLUGS).
 */
Route::get('/{path}', [\App\Http\Controllers\PageController::class, 'show'])
    ->where('path', '^(?!admin|storage|livewire)[a-z0-9\-]+(\/[a-z0-9\-]+)?$')
    ->name('pages.show');

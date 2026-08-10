<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/news', [\App\Http\Controllers\NewsController::class, 'index'])->name('news.index');
Route::get('/news/{slug}', [\App\Http\Controllers\NewsController::class, 'show'])->name('news.show');
Route::get('/resources', [\App\Http\Controllers\ResourceController::class, 'index'])->name('resources.index');
Route::get('/our-work/projects', [\App\Http\Controllers\ProjectController::class, 'index'])->name('projects.index');
Route::get('/our-work/projects/{slug}', [\App\Http\Controllers\ProjectController::class, 'show'])->name('projects.show');
Route::get('/our-work/environmental-governance', [\App\Http\Controllers\BranchController::class, 'environmentalGovernance'])->name('branches.environmental-governance');
Route::get('/our-work/climate-change-and-ozone', [\App\Http\Controllers\BranchController::class, 'climateChangeAndOzone'])->name('branches.climate-change-and-ozone');
Route::get('/our-work/waste-management-and-pollution-control', [\App\Http\Controllers\BranchController::class, 'wasteManagementAndPollutionControl'])->name('branches.waste-management-and-pollution-control');
Route::get('/report-an-environmental-issue', [\App\Http\Controllers\ReportIssueController::class, 'show'])->name('report-issue.show');

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
 *
 * The pattern is built from Page::ROUTE_EXCLUDED_PREFIXES, Page::SLUG_PATTERN
 * and Page::MAX_DEPTH rather than written out here, because PageForm validates
 * editor input against those same constants. When the two were separate
 * literals they disagreed: the route excludes anything *starting with*
 * admin/storage/livewire while Page::RESERVED_SLUGS only matched them exactly,
 * so a page slugged `administration` saved cleanly, showed as Published, and
 * 404ed silently. Change the ceiling or the charset in Page, not here.
 */
Route::get('/{path}', [\App\Http\Controllers\PageController::class, 'show'])
    ->where('path', \App\Models\Page::pathRoutePattern())
    ->name('pages.show');

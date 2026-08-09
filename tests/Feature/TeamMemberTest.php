<?php

use App\Models\TeamMember;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

it('returns active members in sort order', function () {
    TeamMember::create(['name' => 'Second', 'role' => 'Officer', 'sort_order' => 2]);
    TeamMember::create(['name' => 'First', 'role' => 'Director', 'sort_order' => 1]);
    TeamMember::create(['name' => 'Hidden', 'role' => 'Officer', 'sort_order' => 0, 'is_active' => false]);

    expect(TeamMember::active()->pluck('name')->all())->toBe(['First', 'Second']);
});

it('renders a team grid block with active members only', function () {
    TeamMember::create(['name' => 'Visible Person', 'role' => 'Director', 'sort_order' => 1]);
    TeamMember::create(['name' => 'Former Person', 'role' => 'Officer', 'sort_order' => 2, 'is_active' => false]);

    $html = \Illuminate\Support\Facades\Blade::render(
        '<x-page.content :blocks="$blocks" />',
        ['blocks' => [['type' => 'team_grid', 'data' => ['heading' => 'Our Team']]]]
    );

    expect($html)->toContain('Visible Person')
        ->and($html)->toContain('Director')
        ->and($html)->not->toContain('Former Person');
});

it('pins the photo collection to the public disk', function () {
    // Regression guard for the exact class of bug that hit four content
    // models in Spec 1: a media collection missing ->useDisk('public') in
    // registerMediaCollections() uploads fine through Filament but 403s on
    // the public site.
    //
    // This deliberately inspects the registered MediaCollection's diskName
    // rather than calling addMedia() directly and checking the resulting
    // Media row's disk. addMedia() falls back to Spatie's own
    // media-library.disk_name config (also 'public' in this app) when a
    // collection doesn't declare a disk, so a model-only addMedia() call
    // cannot reproduce the actual bug -- only Filament's
    // SpatieMediaLibraryFileUpload falls back to the app's FILESYSTEM_DISK
    // ('local') instead. See TeamMemberResourceTest's upload test for the
    // check that goes through that real path.
    $collection = (new TeamMember)->getMediaCollection('photo');

    expect($collection)->not->toBeNull()
        ->and($collection->diskName)->toBe('public');
});

it('eager-loads media for the team grid block instead of querying per member', function () {
    // Two N+1s have already shipped in this exact pattern (dynamic block
    // querying a model and rendering each record's media). team-grid.blade.php
    // eager-loads media via ->with('media'); without it, photoUrl() and
    // photoAlt() each trigger their own getFirstMedia() query per member, so
    // query count scales with member count instead of staying constant.
    Storage::fake('public');

    TeamMember::factory()->count(3)->create()->each(function (TeamMember $member) {
        $member->addMedia(UploadedFile::fake()->image('photo.jpg'))->toMediaCollection('photo');
    });

    DB::enableQueryLog();

    \Illuminate\Support\Facades\Blade::render(
        '<x-page.content :blocks="$blocks" />',
        ['blocks' => [['type' => 'team_grid', 'data' => ['heading' => 'Our Team']]]]
    );

    $mediaQueries = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'from "media"'));

    expect($mediaQueries)->toHaveCount(1);
});

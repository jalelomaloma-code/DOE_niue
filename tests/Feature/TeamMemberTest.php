<?php

use App\Models\TeamMember;

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

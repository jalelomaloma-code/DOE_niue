<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Deliberately does NOT use WithoutModelEvents: Spatie MediaLibrary's
     * Media::creating event populates the `uuid` column, and Filament's
     * SpatieMediaLibraryFileUpload keys its "existing file" state by that
     * uuid (see SpatieMediaLibraryFileUpload::loadStateFromRelationshipsUsing).
     * WithoutModelEvents swaps the event dispatcher for a NullDispatcher for
     * the entire duration of this method, including everything nested calls
     * run — the trait is checked per-seeder-class, but the swap it makes is
     * global, so DemoContentSeeder's and TeamMemberSeeder's own media rows
     * would silently get a null uuid and never render an existing-image
     * preview in the admin, even though the file itself is attached
     * correctly. (PageSeeder's `image` blocks bypass MediaLibrary entirely —
     * the FileUpload field they mirror stores a plain path string in JSON,
     * not a Media row — so this reasoning doesn't apply to Page, but the
     * seeder still shares the dispatcher with everything else in this run.)
     *
     * Checked, not just assumed, that removing the trait is safe for the
     * other six seeders: RoleSeeder (Role::findOrCreate, a Spatie
     * Permission model with no listeners), DocumentCategorySeeder,
     * QuickLinkSeeder, NavigationItemSeeder and PageSeeder (plain
     * updateOrCreate/create, no model hooks), SettingsSeeder (->update() on
     * SiteSetting/HomepageSetting, also no hooks). The only model hook
     * anywhere in app/Models is HasBlame's
     * creating/updating listener, and it's gated behind auth()->check(),
     * which is always false in a console seeding context regardless of
     * whether events fire.
     */
    public function run(): void
    {
        $this->call([RoleSeeder::class, DocumentCategorySeeder::class, SettingsSeeder::class, QuickLinkSeeder::class, NavigationItemSeeder::class, DemoContentSeeder::class, WasteManagementNewsSeeder::class, PageSeeder::class, TeamMemberSeeder::class]);

        $admin = User::updateOrCreate(
            ['email' => env('DEMO_ADMIN_EMAIL', 'admin@niuedoe.local')],
            [
                'name' => env('DEMO_ADMIN_NAME', 'Niue DoE Admin'),
                'password' => Hash::make(env('DEMO_ADMIN_PASSWORD', 'ChangeThisPassword123!')),
                'email_verified_at' => now(),
            ],
        );

        $admin->assignRole(UserRole::SuperAdmin->value);
    }
}

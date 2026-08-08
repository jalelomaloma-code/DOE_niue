<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

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
     * global, so DemoContentSeeder's own media rows would silently get a
     * null uuid and never render an existing-image preview in the admin,
     * even though the file itself is attached correctly.
     */
    public function run(): void
    {
        $this->call([RoleSeeder::class, DocumentCategorySeeder::class, SettingsSeeder::class, QuickLinkSeeder::class, DemoContentSeeder::class]);

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}

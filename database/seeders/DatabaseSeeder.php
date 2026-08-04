<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\InventoryStages;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'admin',
        ]);

        foreach (InventoryStages::ORDER as $stage) {
            User::factory()->create([
                'name' => InventoryStages::label($stage),
                'email' => $stage.'@example.com',
                'role' => $stage,
            ]);
        }

        $this->call(InventoryRecordSeeder::class);
    }
}

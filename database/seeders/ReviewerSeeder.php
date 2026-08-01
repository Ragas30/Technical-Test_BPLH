<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reviewer = User::updateOrCreate(
            ['email' => 'reviewer@docflow.test'],
            [
                'name' => 'Reviewer',
                'password' => bcrypt('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $reviewer->assignRole(Role::Reviewer);
    }
}

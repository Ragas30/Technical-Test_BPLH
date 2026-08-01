<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class ApplicantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $applicant = User::updateOrCreate(
            ['email' => 'applicant@docflow.test'],
            [
                'name' => 'Applicant',
                'password' => bcrypt('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $applicant->assignRole(Role::Applicant);
    }
}

<?php

namespace Database\Seeders;

use App\Enums\ProjectStatus;
use App\Enums\Role;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BulkDataSeeder extends Seeder
{
    private const APPLICANT_COUNT = 1000;

    private const REVIEWER_COUNT = 1000;

    private const PROJECT_COUNT = 1000;

    private const USER_CHUNK = 500;

    private const PROJECT_CHUNK = 500;

    public function run(): void
    {
        $existingApplicants = User::where('email', 'like', 'pemohon%@docflow.test')->count();
        $existingReviewers = User::where('email', 'like', 'penilai%@docflow.test')->count();

        if ($existingApplicants >= self::APPLICANT_COUNT && $existingReviewers >= self::REVIEWER_COUNT) {
            $this->command->info("Bulk data sudah ada (pemohon: {$existingApplicants}, penilai: {$existingReviewers}). Seeder dilewati.");

            return;
        }

        $this->seedUsersAndRoles();
        $this->seedProjects();
    }

    private function seedUsersAndRoles(): void
    {
        $password = Hash::make('password');
        $now = now();
        $modelType = (new User)->getMorphClass();
        $applicantRoleId = DB::table('roles')->where('name', Role::Applicant->value)->value('id');
        $reviewerRoleId = DB::table('roles')->where('name', Role::Reviewer->value)->value('id');

        $userRows = [];
        $roleRows = [];
        $applicantIds = [];

        for ($i = 1; $i <= self::APPLICANT_COUNT; $i++) {
            $id = (string) Str::uuid();
            $applicantIds[] = $id;
            $userRows[] = $this->userRow($id, "Pemohon {$i}", sprintf('pemohon%03d@docflow.test', $i), $password, $now);
            $roleRows[] = $this->roleRow($applicantRoleId, $modelType, $id);
        }

        for ($i = 1; $i <= self::REVIEWER_COUNT; $i++) {
            $id = (string) Str::uuid();
            $userRows[] = $this->userRow($id, "Penilai {$i}", sprintf('penilai%03d@docflow.test', $i), $password, $now);
            $roleRows[] = $this->roleRow($reviewerRoleId, $modelType, $id);
        }

        foreach (array_chunk($userRows, self::USER_CHUNK) as $chunk) {
            User::insert($chunk);
        }

        foreach (array_chunk($roleRows, 1000) as $chunk) {
            DB::table('model_has_roles')->insert($chunk);
        }

        $this->command->info('Pemohon: '.self::APPLICANT_COUNT.' dan Penilai: '.self::REVIEWER_COUNT.' berhasil dibuat (password: password).');
    }

    /**
     * @return array<string, mixed>
     */
    private function userRow(string $id, string $name, string $email, string $password, Carbon $now): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'email_verified_at' => $now,
            'is_active' => true,
            'remember_token' => Str::random(10),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function roleRow(int $roleId, string $modelType, string $modelId): array
    {
        return [
            'role_id' => $roleId,
            'model_type' => $modelType,
            'model_id' => $modelId,
        ];
    }

    private function seedProjects(): void
    {
        $now = now();
        $applicantIds = User::where('email', 'like', 'pemohon%@docflow.test')->orderBy('email')->pluck('id')->all();

        $maxNumber = (int) preg_replace('/\D+/', '', (string) Project::max('project_number'));
        $startNumber = max($maxNumber + 1, 1);

        $statuses = $this->statusDistribution();
        $titleTemplates = $this->titleTemplates();
        $companies = $this->companyNames();

        $projectRows = [];
        for ($i = 0; $i < self::PROJECT_COUNT; $i++) {
            $number = $startNumber + $i;
            $status = $statuses[$i % count($statuses)];
            $template = $titleTemplates[$i % count($titleTemplates)];
            $company = $companies[$i % count($companies)];
            $isSubmitted = $status !== ProjectStatus::Draft->value;

            $projectRows[] = [
                'id' => (string) Str::uuid(),
                'user_id' => $applicantIds[$i % count($applicantIds)],
                'project_number' => 'PRJ-'.now()->format('Y').'-'.str_pad((string) $number, 5, '0', STR_PAD_LEFT),
                'slug' => 'permohonan-lingkungan-'.$number,
                'title' => "{$template} - {$company} (#{$number})",
                'description' => "Permohonan dokumen lingkungan dari {$company} berupa {$template}. Pengajuan ini diwakili oleh pemohon terdaftar pada sistem DocFlow.",
                'status' => $status,
                'submitted_at' => $isSubmitted ? $now->copy()->subDays($i % 30) : null,
                'created_at' => $now->copy()->subDays($i % 30),
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($projectRows, self::PROJECT_CHUNK) as $chunk) {
            Project::insert($chunk);
        }

        $this->command->info('Project Permohonan: '.self::PROJECT_COUNT.' berhasil dibuat.');
    }

    /**
     * Distribusi status (100 baris, diulang untuk total 1000 project).
     *
     * @return list<string>
     */
    private function statusDistribution(): array
    {
        return [
            ...array_fill(0, 40, ProjectStatus::Draft->value),
            ...array_fill(0, 20, ProjectStatus::Submitted->value),
            ...array_fill(0, 15, ProjectStatus::UnderReview->value),
            ...array_fill(0, 10, ProjectStatus::Revision->value),
            ...array_fill(0, 10, ProjectStatus::Approved->value),
            ...array_fill(0, 5, ProjectStatus::Rejected->value),
        ];
    }

    /**
     * @return list<string>
     */
    private function titleTemplates(): array
    {
        return [
            'Permohonan Persetujuan Lingkungan',
            'Permohonan Izin Lingkungan',
            'Permohonan Persetujuan Teknis UKL-UPL',
            'Permohonan Rekomendasi SPPL',
            'Permohonan Evaluasi Dokumen Lingkungan',
            'Permohonan Perpanjangan Persetujuan Lingkungan',
            'Permohonan Pengelolaan Limbah B3',
            'Permohonan Dokumen Pengelolaan Lingkungan',
        ];
    }

    /**
     * @return list<string>
     */
    private function companyNames(): array
    {
        return [
            'PT Maju Lingkungan Nusantara',
            'PT Karya Hijau Sejahtera',
            'CV Jaya Bersama',
            'PT Bumi Lestari Abadi',
            'PT Energi Bersih Indonesia',
            'CV Nusantara Hijau',
            'PT Reksa Alam Sentosa',
            'PT Cipta Lingkungan',
            'PT Sinar Bumi Mandiri',
            'CV Agro Lestari',
            'PT Mitra Hijau Perkasa',
            'PT Andalan Bumi Sejahtera',
            'CV Sumber Alam Jaya',
            'PT Lestari Alam Nusantara',
            'PT Borneo Hijau Lestari',
            'CV Surya Mandiri',
            'PT Bahari Bumi Indonesia',
            'PT Sinergi Lingkungan',
            'CV Citra Hijau Abadi',
            'PT Nusantara Bumi Sejahtera',
        ];
    }
}

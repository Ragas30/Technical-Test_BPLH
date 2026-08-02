<?php

use App\Enums\ActivityAction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CONSTRAINT = 'activity_logs_action_check';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE activity_logs DROP CONSTRAINT '.self::CONSTRAINT);

        DB::statement(
            'ALTER TABLE activity_logs ADD CONSTRAINT '.self::CONSTRAINT
            .' CHECK (action IN ('.collect(ActivityAction::values())->map(fn ($value) => "'".$value."'")->implode(', ').'))'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE activity_logs DROP CONSTRAINT '.self::CONSTRAINT);

        DB::statement(
            'ALTER TABLE activity_logs ADD CONSTRAINT '.self::CONSTRAINT
            ." CHECK (action IN ('login', 'logout', 'project_created', 'project_updated', 'project_deleted', 'project_submitted', 'document_uploaded', 'document_deleted', 'review_started', 'review_approved', 'review_rejected', 'revision_requested'))"
        );
    }
};

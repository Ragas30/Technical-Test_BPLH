<?php

use App\Enums\ReviewAction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CONSTRAINT = 'review_logs_action_check';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->setAllowedActions(ReviewAction::values());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->setAllowedActions(array_values(array_filter(
            ReviewAction::values(),
            fn (string $action): bool => $action !== ReviewAction::Comment->value,
        )));
    }

    /**
     * @param  array<int, string>  $actions
     */
    private function setAllowedActions(array $actions): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE review_logs DROP CONSTRAINT IF EXISTS '.self::CONSTRAINT);

        DB::statement(sprintf(
            'ALTER TABLE review_logs ADD CONSTRAINT %s CHECK (action IN (%s))',
            self::CONSTRAINT,
            collect($actions)->map(fn (string $action): string => "'".$action."'")->implode(', '),
        ));
    }
};

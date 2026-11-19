<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('registration_type', 30)->default('PARTICIPANT');
            $table->jsonb('custom_answers')->nullable();
            $table->string('division_pref_1', 60)->nullable();
            $table->string('division_pref_2', 60)->nullable();
            $table->string('cv_file_url', 255)->nullable();
            $table->string('selection_status', 30)->nullable();
            $table->string('status', 30)->default('REGISTERED');
            $table->timestampTz('registered_at')->useCurrent();
            $table->timestamps();

            $table->index(['event_id', 'status']);
            $table->index('user_id');
        });

        // Partial unique index for PostgreSQL: prevent double entry while allowing re-registration if cancelled
        DB::statement("
            CREATE UNIQUE INDEX uq_user_event_active
            ON registrations (user_id, event_id)
            WHERE status != 'CANCELLED';
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS uq_user_event_active;");
        Schema::dropIfExists('registrations');
    }
};

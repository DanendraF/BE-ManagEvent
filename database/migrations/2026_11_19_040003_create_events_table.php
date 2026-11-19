<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organizer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('event_categories')->cascadeOnDelete();
            $table->string('title', 150);
            $table->string('slug', 180)->unique();
            $table->text('description')->nullable();
            $table->string('activity_type', 30)->default('PARTICIPANT_EVENT');
            $table->string('poster_url', 255);
            $table->integer('quota_total');
            $table->integer('quota_remaining');
            $table->timestampTz('start_time');
            $table->timestampTz('end_time');
            $table->string('location', 150);
            $table->string('status', 30)->default('DRAFT');
            $table->text('rejection_reason')->nullable();
            $table->boolean('auto_post_ig')->default(true);
            $table->timestamps();

            $table->index(['status', 'start_time']);
            $table->index('organizer_id');
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};

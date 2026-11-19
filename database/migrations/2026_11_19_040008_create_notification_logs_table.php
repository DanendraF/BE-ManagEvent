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
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('event_id')->nullable()->constrained('events')->cascadeOnDelete();
            $table->foreignUuid('registration_id')->nullable()->constrained('registrations')->cascadeOnDelete();
            $table->string('channel', 20);
            $table->string('recipient', 100);
            $table->string('delivery_status', 20)->default('PENDING');
            $table->text('response_payload')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'channel']);
            $table->index('delivery_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};

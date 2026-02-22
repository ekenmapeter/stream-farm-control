<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('device_logs', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->index();          // Links to devices.device_id
            $table->enum('level', ['info', 'warning', 'error', 'critical'])->default('info');
            $table->string('event');                         // e.g. 'fcm_received', 'launch_youtube', 'registration_failed'
            $table->text('message');                          // Human-readable description
            $table->text('stack_trace')->nullable();          // For errors
            $table->json('context')->nullable();             // Extra data (OS version, app version, etc.)
            $table->timestamp('device_timestamp')->nullable(); // Timestamp from the device
            $table->timestamps();                            // Server timestamps

            $table->index(['device_id', 'level']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_logs');
    }
};

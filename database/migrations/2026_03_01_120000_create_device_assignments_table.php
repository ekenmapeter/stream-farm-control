<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('device_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('device_id');
            $table->enum('platform', ['spotify', 'youtube']);
            $table->text('media_url');                          // Spotify URI or YouTube URL
            $table->string('media_title')->nullable();          // Friendly display name
            $table->enum('status', ['pending', 'playing', 'paused', 'stopped', 'failed', 'completed'])
                  ->default('pending');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('started_at')->nullable();        // When device confirmed playback
            $table->timestamps();

            $table->foreign('device_id')
                  ->references('id')
                  ->on('devices')
                  ->onDelete('cascade');

            $table->index(['device_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_assignments');
    }
};

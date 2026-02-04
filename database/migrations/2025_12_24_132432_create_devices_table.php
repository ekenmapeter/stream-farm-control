<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(); // Phone nickname
            $table->string('device_id')->unique(); // Unique phone identifier
            $table->text('fcm_token')->unique(); // Firebase Cloud Messaging token
            $table->string('status')->default('offline'); // online, offline, streaming
            $table->timestamp('last_seen')->nullable();
            $table->json('metadata')->nullable(); // Phone model, Android version, etc.
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};

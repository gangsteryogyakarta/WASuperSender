<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waha_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('session_name')->unique();
            $table->string('phone_number')->nullable();
            $table->enum('status', ['starting', 'scan_qr_code', 'working', 'failed', 'stopped'])->default('starting');
            $table->timestamp('last_seen_at')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();
        });

        Schema::create('message_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('category')->nullable();
            $table->text('content');
            $table->string('media_path')->nullable();
            $table->json('variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_templates');
        Schema::dropIfExists('waha_sessions');
    }
};

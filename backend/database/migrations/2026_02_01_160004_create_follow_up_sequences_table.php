<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_up_sequences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('trigger_event');
            $table->timestamps();
        });

        Schema::create('sequence_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sequence_id');
            $table->integer('step_order');
            $table->integer('delay_hours');
            $table->text('message_template');
            $table->string('media_path')->nullable();
            $table->timestamps();

            $table->foreign('sequence_id')->references('id')->on('follow_up_sequences')->onDelete('cascade');
        });

        Schema::create('contact_sequences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('contact_id');
            $table->uuid('sequence_id');
            $table->integer('current_step')->default(0);
            $table->enum('status', ['active', 'paused', 'completed', 'cancelled'])->default('active');
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();

            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('sequence_id')->references('id')->on('follow_up_sequences')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_sequences');
        Schema::dropIfExists('sequence_steps');
        Schema::dropIfExists('follow_up_sequences');
    }
};

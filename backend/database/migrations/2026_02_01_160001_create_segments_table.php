<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('segments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('criteria');
            $table->integer('contact_count')->default(0);
            $table->timestamps();
        });

        Schema::create('contact_segment', function (Blueprint $table) {
            $table->uuid('contact_id');
            $table->uuid('segment_id');
            $table->timestamps();
            $table->primary(['contact_id', 'segment_id']);
            
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('segment_id')->references('id')->on('segments')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_segment');
        Schema::dropIfExists('segments');
    }
};

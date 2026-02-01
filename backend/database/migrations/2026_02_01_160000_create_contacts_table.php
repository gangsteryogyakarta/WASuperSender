<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('phone', 20)->index();
            $table->string('name');
            $table->string('email')->nullable();
            $table->enum('lead_status', [
                'new', 'contacted', 'qualified', 'proposal', 
                'negotiation', 'closed_won', 'closed_lost'
            ])->default('new');
            $table->string('vehicle_interest')->nullable();
            $table->decimal('budget', 15, 2)->nullable();
            $table->string('source')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('assigned_to')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};

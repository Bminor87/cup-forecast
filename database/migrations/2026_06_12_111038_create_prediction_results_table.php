<?php

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
        Schema::create('prediction_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tournament_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('prediction_field_id')->constrained('prediction_fields')->cascadeOnDelete();
            $table->foreignId('tournament_match_id')->nullable()->constrained('matches')->cascadeOnDelete();
            $table->string('context_key');
            $table->json('value');
            $table->string('status');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['tournament_id', 'prediction_field_id', 'context_key']);
            $table->index(['tournament_id', 'status']);
            $table->index(['prediction_field_id', 'context_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prediction_results');
    }
};

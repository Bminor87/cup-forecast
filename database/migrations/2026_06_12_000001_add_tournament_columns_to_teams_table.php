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
        Schema::table('teams', function (Blueprint $table): void {
            $table->string('sport_type')->nullable()->after('is_personal');
            $table->string('competition_mode')->nullable()->after('sport_type');
            $table->string('status')->nullable()->after('competition_mode');
            $table->timestamp('starts_at')->nullable()->after('status');
            $table->timestamp('ends_at')->nullable()->after('starts_at');
            $table->string('timezone')->default('UTC')->after('ends_at');
            $table->string('scoring_strategy_key')->nullable()->after('timezone');
            $table->unsignedSmallInteger('scoring_strategy_version')->nullable()->after('scoring_strategy_key');
            $table->json('settings')->nullable()->after('scoring_strategy_version');
            $table->timestamp('archived_at')->nullable()->after('settings');

            $table->index(['sport_type', 'competition_mode'], 'teams_sport_competition_idx');
            $table->index('status', 'teams_status_idx');
            $table->index('archived_at', 'teams_archived_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table): void {
            $table->dropIndex('teams_sport_competition_idx');
            $table->dropIndex('teams_status_idx');
            $table->dropIndex('teams_archived_at_idx');

            $table->dropColumn([
                'sport_type',
                'competition_mode',
                'status',
                'starts_at',
                'ends_at',
                'timezone',
                'scoring_strategy_key',
                'scoring_strategy_version',
                'settings',
                'archived_at',
            ]);
        });
    }
};

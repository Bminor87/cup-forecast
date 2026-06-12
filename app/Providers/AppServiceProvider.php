<?php

namespace App\Providers;

use App\Domain\Tournaments\Models\Player;
use App\Domain\Tournaments\Models\Prediction;
use App\Domain\Tournaments\Models\PredictionField;
use App\Domain\Tournaments\Models\PredictionResult;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Domain\Tournaments\Models\TournamentTeam;
use App\Domain\Tournaments\Policies\PlayerPolicy;
use App\Domain\Tournaments\Policies\PredictionFieldPolicy;
use App\Domain\Tournaments\Policies\PredictionPolicy;
use App\Domain\Tournaments\Policies\PredictionResultPolicy;
use App\Domain\Tournaments\Policies\TournamentMatchPolicy;
use App\Domain\Tournaments\Policies\TournamentPolicy;
use App\Domain\Tournaments\Policies\TournamentTeamPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Tournament::class, TournamentPolicy::class);
        Gate::policy(TournamentTeam::class, TournamentTeamPolicy::class);
        Gate::policy(Player::class, PlayerPolicy::class);
        Gate::policy(TournamentMatch::class, TournamentMatchPolicy::class);
        Gate::policy(Prediction::class, PredictionPolicy::class);
        Gate::policy(PredictionField::class, PredictionFieldPolicy::class);
        Gate::policy(PredictionResult::class, PredictionResultPolicy::class);

        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}

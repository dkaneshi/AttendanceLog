<?php

declare(strict_types=1);

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
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
        $this->configureCommands();
        $this->configureModels();
        $this->configureDates();
        $this->configureVite();
    }

    /**
     * Configure the application's commands
     */
    private function configureCommands(): void
    {
        DB::prohibitDestructiveCommands(
            $this->app->isProduction(),
        );
    }

    /**
     * Configure the application's models.
     */
    private function configureModels(): void
    {
        //        Model::preventLazyLoading(! $this->app->environment('testing'));

        Model::preventSilentlyDiscardingAttributes();

        Model::preventAccessingMissingAttributes();

        Model::unguard();
    }

    /**
     * Configure the application's Vite.
     */
    private function configureVite(): void
    {
        Vite::usePrefetchStrategy('aggressive');
    }

    /**
     * Configure the application's dates.
     */
    private function configureDates(): void
    {
        Date::use(CarbonImmutable::class);
    }
}

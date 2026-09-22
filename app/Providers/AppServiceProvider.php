<?php

namespace App\Providers;

use App\Repositories\Contracts\GoalRepository;
use App\Repositories\Contracts\TransactionRepository;
use App\Repositories\Json\JsonGoalRepository;
use App\Repositories\Json\JsonTransactionRepository;
use App\Support\JsonStore;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * Swap these bindings to move from JSON files to another storage driver
     * (e.g. Eloquent) without touching services or controllers.
     */
    public function register(): void
    {
        $this->app->bind(GoalRepository::class, fn (): JsonGoalRepository => new JsonGoalRepository(
            $this->jsonStore('goals.json'),
        ));

        $this->app->bind(TransactionRepository::class, fn (): JsonTransactionRepository => new JsonTransactionRepository(
            $this->jsonStore('transactions.json'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    private function jsonStore(string $file): JsonStore
    {
        return new JsonStore(rtrim(config('savings.storage_path'), '/\\').DIRECTORY_SEPARATOR.$file);
    }
}

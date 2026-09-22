<?php

use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\GoalController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\TransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::apiResource('goals', GoalController::class)->whereNumber('goal');
    Route::apiResource('transactions', TransactionController::class)->whereNumber('transaction');

    Route::prefix('reports')->name('reports.')->controller(ReportController::class)->group(function () {
        Route::get('history', 'history')->name('history');
        Route::get('goals', 'goals')->name('goals');
        Route::get('monthly', 'monthly')->name('monthly');
        Route::get('status', 'status')->name('status');
    });
});

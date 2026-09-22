<?php

use Illuminate\Support\Facades\Route;

/*
 * The Vue router owns every non-API path.
 */
Route::view('/{any?}', 'app')->where('any', '^(?!api(/|$)|up$).*$')->name('app');

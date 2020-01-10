<?php

Route::namespace('VATSIMUK\Support\Auth\Controllers')
    ->middleware(\Illuminate\Session\Middleware\StartSession::class)
    ->prefix('auth')
    ->name('auth.')
    ->group(function () {
    Route::get('login', 'AuthenticationController@login')->name('login');
    Route::get('login/verify', 'AuthenticationController@verifyLogin')->name('login.verify');
});

<?php

use Illuminate\Support\Facades\Route;

// This is your only web route. It just shows the default Laravel page.
// We removed the database code from here so it doesn't crash the session!
Route::get('/', function () {
    return view('welcome');
});

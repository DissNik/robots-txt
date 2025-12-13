<?php

use DissNik\RobotsTxt\Http\Controllers\RobotsTxtController;
use Illuminate\Support\Facades\Route;

Route::get('robots.txt', RobotsTxtController::class)
    ->name('robots-txt');

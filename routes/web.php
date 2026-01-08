<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return [
        'Ley Invest'    => 'Official Backend API Server',
        'app'           => app()->version()];
});







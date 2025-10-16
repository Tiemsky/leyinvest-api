<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});
Route::get('/webmail', function () {
    return 'test';
});



require __DIR__.'/auth.php';

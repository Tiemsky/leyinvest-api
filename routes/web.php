<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return [
        'Ley Invest'    => 'Official Backend API Server',
        'app'           => app()->version()];
});

Route::get('test', function () {
    return view('emails.otp.resend', [
        'user' => (object)[
            'prenom' => 'John',
            'nom' => 'Doe',
            'email' => 'tiafranck31@yahoo.fr'
        ],
        'otpCode' => '654321',
        'type' => 'resend',
        'expiry' => 10,

    ]);
});







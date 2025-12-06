<?php

use App\Http\Controllers\Api\V1\DocumentController;


Route::middleware(['guest'])->group(function () {

    // 1. Route pour TÉLÉCHARGEMENT DIRECT (Content-Disposition: attachment)
    Route::get('/documents/{document}/download', [DocumentController::class, 'servePdf'])
         ->name('api.documents.download')
         ->scopeBindings();

    // 2. Route pour OUVRIR DANS LE NAVIGATEUR (Content-Disposition: inline)
    Route::get('/documents/{document}/view', [DocumentController::class, 'servePdf'])
         ->name('api.documents.view')
         ->scopeBindings();
});

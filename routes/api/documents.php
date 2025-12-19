<?php
use App\Http\Controllers\Api\V1\DocumentController;
use Illuminate\Support\Facades\Route;

// Ici, on utilise 'signed' pour sécuriser le lien de téléchargement sans forcer le login immédiat (email links)
// Ou 'auth:sanctum' selon ton besoin. Je mets 'signed' par sécurité par défaut pour les exports.
Route::middleware(['throttle:api'])->group(function () {
    Route::get('/documents/{document}/download', [DocumentController::class, 'servePdf'])
            ->name('api.documents.download')
            ->scopeBindings();
    Route::get('/documents/{document}/view', [DocumentController::class, 'servePdf'])
            ->name('api.documents.view')
            ->scopeBindings();
});

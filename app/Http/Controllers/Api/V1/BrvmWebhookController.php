<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\BrvmSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BrvmWebhookController extends Controller
{
    protected BrvmSyncService $syncService;

    public function __construct(BrvmSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    public function handle(Request $request)
    {
        // Log léger pour traçabilité
        $dataType = $request->input('data_type');
        Log::info('Webhook BRVM reçu', [
            'data_type' => $dataType,
            'payload_count' => is_array($request->input('data')) ? count($request->input('data')) : 'N/A'
        ]);

        // Validation du payload racine
        if (!$dataType || !is_array($request->input('data'))) {
            Log::warning('Payload invalide reçu', [
                'full_input' => $request->all()
            ]);
            return response()->json(['message' => 'Payload invalide: data_type ou data manquant'], 400);
        }

        $payload = $request->input('data');

        try {
            $result = $this->syncService->handlePayload($dataType, $payload);
            return response()->json($result, 200);
        } catch (\UnhandledMatchError $e) {
            Log::error("Type de donnée non géré: $dataType");
            return response()->json(['message' => "Type '$dataType' non pris en charge"], 422);
        } catch (\Exception $e) {
            Log::error(" Erreur critique lors de la synchronisation ($dataType)", [
                'error' => $e->getMessage(),
                'payload_sample' => array_slice($payload, 0, 3),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Erreur interne du serveur'], 500);
        }
    }
}

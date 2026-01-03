<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\BrvmSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BrvmWebhookController extends Controller
{
    protected $syncService;

    // Injection du service via le constructeur
    public function __construct(BrvmSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    public function handle(Request $request)
    {
        $dataType = $request->input('data_type');
        $payload = $request->input('data');

        if (!$dataType || !$payload) {
            return response()->json(['message' => 'Payload invalide'], 400);
        }

        try {
            // Appel au service pour le traitement
            $result = $this->syncService->handlePayload($dataType, $payload);

            return response()->json([
                'status' => 'success',
                'message' => $result['message']
            ], 200);

        } catch (\UnhandledMatchError $e) {
            return response()->json(['message' => "Type $dataType non géré"], 422);
        } catch (\Exception $e) {
            Log::error("Erreur Webhook BRVM ($dataType) : " . $e->getMessage());
            return response()->json(['message' => 'Erreur lors de la synchronisation'], 500);
        }
    }
}

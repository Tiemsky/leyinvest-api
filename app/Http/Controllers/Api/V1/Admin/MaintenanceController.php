<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MaintenanceController extends Controller
{

    /**
     * Déclenche le nettoyage des inscriptions incomplètes via l'API admin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */

     /**
 * @OA\Post(
 *     path="/api/v1/admin/maintenance/cleanup-incomplete-registrations",
 *     operationId="cleanupIncompleteRegistrations",
 *     tags={"Admin - Maintenance"},
 *     summary="Nettoyage des inscriptions incomplètes",
 *     description="Cette route permet à un administrateur d'exécuter ou de simuler (dry-run) le nettoyage des inscriptions incomplètes via l'appel de la commande Artisan `registrations:cleanup`.",
 *     security={{"sanctum": {}}},
 *
 *     @OA\RequestBody(
 *         required=false,
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="dry_run",
 *                 type="boolean",
 *                 description="Exécute la commande en mode simulation sans supprimer de données.",
 *                 example=true
 *             ),
 *             @OA\Property(
 *                 property="hours",
 *                 type="integer",
 *                 minimum=1,
 *                 maximum=168,
 *                 description="Âge maximal (en heures) des inscriptions incomplètes à nettoyer. Par défaut : 24 heures.",
 *                 example=48
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Nettoyage exécuté ou simulé avec succès.",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="dry_run", type="boolean", example=true),
 *             @OA\Property(property="hours_threshold", type="integer", example=48),
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Simulation du nettoyage terminée. Aucune donnée n’a été supprimée."
 *             ),
 *             @OA\Property(
 *                 property="artisan_output",
 *                 type="string",
 *                 example="✔️ 3 inscriptions incomplètes détectées (mode simulation)"
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=400,
 *         description="Requête invalide (erreur de validation).",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 @OA\Property(
 *                     property="hours",
 *                     type="array",
 *                     @OA\Items(type="string", example="The hours field must be between 1 and 168.")
 *                 )
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=401,
 *         description="Non authentifié — jeton Sanctum manquant ou invalide.",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=500,
 *         description="Erreur interne du serveur ou problème lors de l'exécution de la commande Artisan.",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Erreur lors de l’exécution de la commande."),
 *             @OA\Property(property="error", type="string", example="Command not found: registrations:cleanup")
 *         )
 *     )
 * )
 */
    public function cleanupIncompleteRegistrations(Request $request)
    {
        // Validation manuelle légère (Artisan gère le cast, mais on sécurise l'input)
        $validator = Validator::make($request->all(), [
            'dry_run' => 'sometimes|boolean',
            'hours'   => 'sometimes|integer|min=1|max=168', // 1h à 7 jours
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $hours = (int) $request->input('hours', 24);
        $dryRun = (bool) $request->input('dry_run', false);

        // ⚠️ Sécurité : limiter la fréquence d'appel en production (optionnel mais recommandé)
        // Ex: rate limiting ou vérification de rôle élevé

        try {
            $exitCode = Artisan::call('registrations:cleanup', [
                '--dry-run' => $dryRun,
                '--hours'   => $hours,
            ]);

            $output = Artisan::output();

            if ($exitCode !== 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l’exécution de la commande.',
                    'output'  => $output,
                ], 500);
            }

            return response()->json([
                'success'           => true,
                'dry_run'           => $dryRun,
                'hours_threshold'   => $hours,
                'message'           => $dryRun
                    ? 'Simulation du nettoyage terminée. Aucune donnée n’a été supprimée.'
                    : 'Nettoyage définitif des inscriptions incomplètes effectué avec succès.',
                'artisan_output'    => trim($output),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur inattendue est survenue.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Contactez l’administrateur système.',
            ], 500);
        }
    }
}

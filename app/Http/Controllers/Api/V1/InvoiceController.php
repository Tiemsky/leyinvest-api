<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    /**
     * Liste des factures de l'utilisateur
     * GET /api/v1/invoices
     */

    /**
     * @OA\Get(
     *     path="/api/v1/invoices",
     *     summary="Lister les factures de l'utilisateur",
     *     description="Retourne la liste des factures associées à l'utilisateur authentifié.",
     *     operationId="listUserInvoices",
     *     tags={"Invoices"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Factures récupérées avec succès",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Factures récupérées avec succès."),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/InvoiceListItem")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // 1. Récupération des factures avec Eager Loading pour l'index
        // (Peut-être paginé, mais nous gardons la simplicité de l'exemple)
        $invoices = $this->invoiceService->getUserInvoices($user);

        return response()->json([
            'success' => true,
            'message' => 'Factures récupérées avec succès.',
            'data' => InvoiceResource::collection($invoices),
        ]);
    }

    /**
     * Détails d'une facture
     * GET /api/v1/invoices/{invoiceNumber}
     */
    /**
     * @OA\Get(
     *     path="/api/v1/invoices/{invoiceNumber}",
     *     summary="Afficher les détails d'une facture",
     *     description="Retourne les informations complètes d'une facture appartenant à l'utilisateur authentifié.",
     *     operationId="showInvoice",
     *     tags={"Invoices"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="invoiceNumber",
     *         in="path",
     *         required=true,
     *         description="Numéro unique de la facture",
     *
     *         @OA\Schema(type="string", example="INV-2025-00045")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Facture récupérée avec succès",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Facture récupérée avec succès."),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/InvoiceResource"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Facture introuvable"),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function show(Request $request, string $invoiceNumber): JsonResponse
    {
        $user = $request->user();

        // 1. Récupération de la facture avec toutes les relations nécessaires
        $invoice = $user->invoices()
            ->where('invoice_number', $invoiceNumber)
            // Chargement Eager des relations nécessaires pour la Resource
            ->with(['subscription.plan', 'coupon'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Facture récupérée avec succès.',
            'data' => new InvoiceResource($invoice),
        ]);
    }
}

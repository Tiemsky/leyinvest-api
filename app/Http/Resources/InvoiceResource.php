<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\PlanResource; // Si Plan est une ressource, utiliser la sienne

/**
 * @OA\Schema(
 *     schema="InvoiceResource",
 *     type="object",
 *     description="Détails complets d'une facture",
 *
 *     @OA\Property(property="id", type="integer", example=45),
 *     @OA\Property(property="invoice_number", type="string", example="INV-2025-00045"),
 *     @OA\Property(property="status", type="string", example="PAID"),
 *     @OA\Property(property="currency", type="string", example="XOF"),
 *
 *     @OA\Property(
 *         property="amounts",
 *         type="object",
 *         @OA\Property(property="subtotal", type="number", format="float", example=23000),
 *         @OA\Property(property="discount", type="number", format="float", example=3000),
 *         @OA\Property(property="tax", type="number", format="float", example=0),
 *         @OA\Property(property="total", type="number", format="float", example=20000),
 *         @OA\Property(property="total_due", type="number", format="float", example=0)
 *     ),
 *
 *     @OA\Property(
 *         property="dates",
 *         type="object",
 *         @OA\Property(property="issued_at", type="string", format="date-time", example="2025-02-01T10:15:00Z"),
 *         @OA\Property(property="due_at", type="string", format="date-time", example="2025-02-05T23:59:59Z"),
 *         @OA\Property(property="paid_at", type="string", format="date-time", example="2025-02-01T10:16:10Z")
 *     ),
 *
 *     @OA\Property(
 *         property="plan_details",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="name", type="string", example="Plan Premium"),
 *         @OA\Property(property="slug", type="string", example="premium")
 *     ),
 *
 *     @OA\Property(
 *         property="coupon",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="code", type="string", example="PROMO10"),
 *         @OA\Property(property="type", type="string", example="percent")
 *     ),
 *
 *     @OA\Property(
 *         property="download_url",
 *         type="string",
 *         format="uri",
 *         example="https://api.example.com/api/v1/invoices/INV-2025-00045/download"
 *     )
 * )
 */

class InvoiceResource extends JsonResource
{
    /**
     * Transforme la ressource en tableau.
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // On s'assure que les relations existent pour éviter les erreurs
        $plan = $this->whenLoaded('subscription', function () {
            return $this->subscription?->plan;
        });

        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'status' => $this->status,
            'currency' => $this->currency,

            // Détails financiers complets
            'amounts' => [
                'subtotal' => $this->subtotal,
                'discount' => $this->discount,
                'tax' => $this->tax,
                'total' => $this->total,
                'total_due' => $this->total_due, // Utile pour les factures UNPAID
            ],

            // Informations de date formatées
            'dates' => [
                'issued_at' => $this->issued_at?->toIsoString(),
                'due_at' => $this->due_at?->toIsoString(),
                'paid_at' => $this->paid_at?->toIsoString(),
            ],

            // Relation(s) optionnelle(s) — Utilisation de whenLoaded pour l'Eager Loading
            'plan_details' => $this->when($plan, [
                'name' => $plan->nom,
                'slug' => $plan->slug,
            ]),

            // Détails du coupon
            'coupon' => $this->whenLoaded('coupon', function () {
                return [
                    'code' => $this->coupon->code,
                    'type' => $this->coupon->type, // Ex: 'percent' ou 'fixed'
                ];
            }),

            // Lien de téléchargement (si applicable)
            'download_url' => route('invoices.download', $this->invoice_number),
        ];
    }

    /**
     * Pour une collection (méthode index), on peut simplifier la vue
     */

    /**
     * @OA\Schema(
     *     schema="InvoiceListItem",
     *     type="object",
     *     description="Facture (vue simplifiée pour la liste)",
     *     @OA\Property(property="id", type="integer", example=45),
     *     @OA\Property(property="invoice_number", type="string", example="INV-2025-00045"),
     *     @OA\Property(property="status", type="string", example="PAID"),
     *     @OA\Property(property="total", type="number", format="float", example=25000),
     *     @OA\Property(property="currency", type="string", example="XOF"),
     *     @OA\Property(property="issued_at", type="string", format="date-time", example="2025-02-01T10:15:00Z")
     * )
     */

    public static function collection(mixed $resource)
    {
        // Surcharge pour retourner une version simplifiée dans la liste (index)
        return parent::collection($resource)->through(function ($invoice) {
            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status,
                'total' => $invoice->total,
                'currency' => $invoice->currency,
                'issued_at' => $invoice->issued_at?->toIsoString(),
            ];
        });
    }
}

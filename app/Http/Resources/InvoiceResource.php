<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transforme la ressource en tableau.
     *
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
     *
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

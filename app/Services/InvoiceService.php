<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\User;

class InvoiceService
{
    /**
     * Créer une facture pour une souscription
     */
    public function createForSubscription(
        Subscription $subscription,
        float $subtotal,
        float $discount = 0,
        ?Coupon $coupon = null,
        array $options = []
    ): Invoice {
        // La taxe est généralement calculée sur le subtotal - discount
        $taxRate = $options['tax_rate'] ?? 0.00; // Utiliser un taux au lieu d'un montant
        $tax = round(($subtotal - $discount) * $taxRate, 2);

        $total = $subtotal - $discount + $tax;

        return Invoice::create([
            'invoice_number' => Invoice::generateInvoiceNumber(), // Assurez-vous que cette méthode existe dans le modèle Invoice
            'user_id' => $subscription->user_id,
            'subscription_id' => $subscription->id,
            'coupon_id' => $coupon?->id,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $total,
            'currency' => $options['currency'] ?? 'XOF',
            'status' => $options['status'] ?? 'pending',
            'issued_at' => $options['issued_at'] ?? now(),
            'due_at' => $options['due_at'] ?? now()->addDays(7),
            'metadata' => array_merge($options['metadata'] ?? [], [
                'plan_name' => $subscription->plan->nom,
                'plan_slug' => $subscription->plan->slug,
                'billing_cycle' => $subscription->plan->billing_cycle,
                'coupon_code' => $coupon?->code,
                'tax_rate' => $taxRate, // Stocker le taux de taxe
            ]),
        ]);
    }

    /**
     * Obtenir les factures d'un utilisateur
     */
    public function getUserInvoices(User $user, array $filters = [])
    {
        return $user->invoices()
            ->with(['subscription.plan', 'coupon'])
            ->applyFilters($filters) // Utilisation du Scope
            ->orderBy('issued_at', 'desc')
            ->get();
    }

    /**
     * Obtenir les statistiques des factures
     */
    public function getStats(array $filters = []): array
    {
        $query = Invoice::query()->applyFilters($filters); // Utilisation du Scope

        $paidQuery = (clone $query)->where('status', 'paid');
        $pendingQuery = (clone $query)->where('status', 'pending');

        return [
            'total_invoices' => $query->count(),
            'paid_invoices' => $paidQuery->count(),
            'pending_invoices' => $pendingQuery->count(),
            'failed_invoices' => (clone $query)->where('status', 'failed')->count(),
            'overdue_invoices' => (clone $pendingQuery)
                ->where('due_at', '<', now())->count(),
            'total_revenue' => $paidQuery->sum('total'),
            'pending_revenue' => $pendingQuery->sum('total'),
            'total_discounts' => $query->sum('discount'),
            'average_invoice_amount' => $paidQuery->avg('total'),
        ];
    }

    /**
     * Marquer une facture comme payée
     */
    public function markAsPaid(Invoice $invoice, array $paymentData = []): bool
    {
        return $invoice->markAsPaid($paymentData);
    }

    /**
     * Marquer une facture comme échouée
     */
    public function markAsFailed(Invoice $invoice, ?string $reason = null): bool
    {
        return $invoice->markAsFailed($reason);
    }

    /**
     * Créer une facture pro-forma (devis)
     */
    public function createProforma(
        User $user,
        float $amount,
        array $items = [],
        array $options = []
    ): Invoice {
        return Invoice::create([
            'invoice_number' => 'PROFORMA-'.Invoice::generateInvoiceNumber(),
            'user_id' => $user->id,
            'subscription_id' => $options['subscription_id'] ?? null,
            'coupon_id' => $options['coupon_id'] ?? null,
            'subtotal' => $amount,
            'discount' => $options['discount'] ?? 0,
            'tax' => $options['tax'] ?? 0,
            'total' => $amount - ($options['discount'] ?? 0) + ($options['tax'] ?? 0),
            'currency' => $options['currency'] ?? 'XOF',
            'status' => 'draft',
            'issued_at' => now(),
            'due_at' => $options['due_at'] ?? now()->addDays(30),
            'metadata' => array_merge($options['metadata'] ?? [], [
                'type' => 'proforma',
                'items' => $items,
            ]),
        ]);
    }

    /**
     * Envoyer une facture par email (à implémenter avec Mail)
     */
    public function send(Invoice $invoice): bool
    {
        // À implémenter avec le système de mail
        // Mail::to($invoice->user->email)->send(new InvoiceMail($invoice));

        // Pour l'instant, on marque juste dans les métadonnées
        $invoice->update([
            'metadata' => array_merge($invoice->metadata ?? [], [
                'sent_at' => now()->toISOString(),
            ]),
        ]);

        return true;
    }

    /**
     * Générer un PDF de facture (à implémenter)
     */
    public function generatePdf(Invoice $invoice): string
    {
        // À implémenter avec une lib comme dompdf ou snappy
        // return PDF::loadView('invoices.pdf', compact('invoice'))->output();
        // Pour l'instant, retourner juste un placeholder
        return 'PDF generation not implemented yet';
    }

    /**
     * Rembourser une facture
     */
    public function refund(Invoice $invoice, ?string $reason = null): bool
    {
        if ($invoice->status !== 'paid') {
            return false;
        }

        return $invoice->update([
            'status' => 'refunded',
            'metadata' => array_merge($invoice->metadata ?? [], [
                'refund_reason' => $reason,
                'refunded_at' => now()->toISOString(),
            ]),
        ]);
    }

    /**
     * Obtenir les factures en retard
     */
    public function getOverdueInvoices()
    {
        return Invoice::where('status', 'pending')
            ->where('due_at', '<', now())
            ->with(['user', 'subscription.plan'])
            ->get();
    }

    /**
     * Calculer le total des revenus sur une période
     */
    public function calculateRevenue(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        $invoices = Invoice::where('status', 'paid')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->get();

        return [
            'period_start' => $startDate->toDateString(),
            'period_end' => $endDate->toDateString(),
            'total_revenue' => $invoices->sum('total'),
            'total_invoices' => $invoices->count(),
            'average_invoice' => $invoices->avg('total'),
            'total_discounts' => $invoices->sum('discount'),
            'total_tax' => $invoices->sum('tax'),
            'net_revenue' => $invoices->sum('subtotal'),
        ];
    }
}

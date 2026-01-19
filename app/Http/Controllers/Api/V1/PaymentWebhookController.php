<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Contrôleur de Webhook pour les paiements
 *
 * Ce contrôleur gère les webhooks de différents opérateurs de paiement.
 * Adaptez les méthodes selon l'opérateur que vous choisissez.
 */
class PaymentWebhookController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Webhook générique - À adapter selon votre opérateur
     *
     * Exemples d'opérateurs supportés:
     * - Stripe
     * - Fedapay (Mobile Money Afrique)
     * - PayPal
     * - Cinetpay
     * - etc.
     */
    public function handleWebhook(Request $request, string $provider)
    {
        // Log du webhook reçu (important pour debug)
        Log::info("Webhook reçu de {$provider}", [
            'payload' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        // Routage selon le provider
        return match ($provider) {
            'stripe' => $this->handleStripeWebhook($request),
            'fedapay' => $this->handleFedapayWebhook($request),
            'paypal' => $this->handlePaypalWebhook($request),
            'cinetpay' => $this->handleCinetpayWebhook($request),
            default => response()->json(['error' => 'Provider non supporté'], 400),
        };
    }

    /**
     * Webhook Stripe
     * Documentation: https://stripe.com/docs/webhooks
     */
    protected function handleStripeWebhook(Request $request)
    {
        // 1. Vérifier la signature Stripe (IMPORTANT pour la sécurité)
        $signature = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            // Décommentez quand vous aurez le SDK Stripe installé
            // $event = \Stripe\Webhook::constructEvent(
            //     $request->getContent(),
            //     $signature,
            //     $webhookSecret
            // );

            // Pour l'instant, on simule
            $event = json_decode($request->getContent(), true);

            // 2. Gérer les événements selon leur type
            switch ($event['type'] ?? null) {
                case 'checkout.session.completed':
                case 'payment_intent.succeeded':
                    return $this->handleSuccessfulPayment($event['data']['object']);

                case 'payment_intent.payment_failed':
                    return $this->handleFailedPayment($event['data']['object']);

                case 'customer.subscription.deleted':
                    return $this->handleSubscriptionCanceled($event['data']['object']);

                default:
                    Log::info("Stripe event non géré: {$event['type']}");

                    return response()->json(['status' => 'ignored']);
            }

        } catch (\Exception $e) {
            Log::error('Erreur webhook Stripe', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Webhook invalide'], 400);
        }
    }

    /**
     * Webhook Fedapay (Mobile Money: Orange, MTN, Moov)
     * Documentation: https://docs.fedapay.com/webhooks/
     */
    protected function handleFedapayWebhook(Request $request)
    {
        // 1. Vérifier la signature Fedapay
        $signature = $request->header('X-Fedapay-Signature');
        $webhookSecret = config('services.fedapay.webhook_secret');

        $computedSignature = hash_hmac('sha256', $request->getContent(), $webhookSecret);

        if (! hash_equals($computedSignature, $signature ?? '')) {
            Log::warning('Signature Fedapay invalide');

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // 2. Récupérer l'événement
        $event = $request->input('entity');
        $eventType = $request->input('event');

        // 3. Gérer selon le type d'événement
        switch ($eventType) {
            case 'transaction.approved':
                return $this->handleSuccessfulPayment([
                    'id' => $event['id'],
                    'amount' => $event['amount'] / 100, // Fedapay utilise les centimes
                    'currency' => $event['currency'],
                    'metadata' => $event['description'] ?? null,
                ]);

            case 'transaction.declined':
            case 'transaction.canceled':
                return $this->handleFailedPayment([
                    'id' => $event['id'],
                    'metadata' => $event['description'] ?? null,
                ]);

            default:
                return response()->json(['status' => 'ignored']);
        }
    }

    /**
     * Webhook PayPal
     * Documentation: https://developer.paypal.com/docs/api-basics/notifications/webhooks/
     */
    protected function handlePaypalWebhook(Request $request)
    {
        // 1. Vérifier la signature PayPal (utilisez le SDK PayPal)
        // Note: La vérification PayPal est complexe, utilisez leur SDK

        $event = $request->all();
        $eventType = $event['event_type'] ?? null;

        switch ($eventType) {
            case 'PAYMENT.SALE.COMPLETED':
                return $this->handleSuccessfulPayment($event['resource']);

            case 'PAYMENT.SALE.DENIED':
            case 'PAYMENT.SALE.REFUNDED':
                return $this->handleFailedPayment($event['resource']);

            case 'BILLING.SUBSCRIPTION.CANCELLED':
                return $this->handleSubscriptionCanceled($event['resource']);

            default:
                return response()->json(['status' => 'ignored']);
        }
    }

    /**
     * Webhook Cinetpay (Mobile Money & Cartes Afrique)
     * Documentation: https://docs.cinetpay.com/api/
     */
    protected function handleCinetpayWebhook(Request $request)
    {
        // Cinetpay envoie généralement un POST avec cpm_trans_id
        $transactionId = $request->input('cpm_trans_id');
        $apikey = config('services.cinetpay.apikey');
        $siteId = config('services.cinetpay.site_id');

        // Vérifier le statut de la transaction via API Cinetpay
        // (Cinetpay recommande de vérifier côté serveur)

        if ($request->input('cpm_result') === '00') {
            // Paiement réussi
            return $this->handleSuccessfulPayment([
                'id' => $transactionId,
                'amount' => $request->input('cpm_amount'),
                'currency' => $request->input('cpm_currency'),
                'metadata' => $request->input('cpm_custom'),
            ]);
        } else {
            // Paiement échoué
            return $this->handleFailedPayment([
                'id' => $transactionId,
                'metadata' => $request->input('cpm_custom'),
            ]);
        }
    }

    /**
     * Traiter un paiement réussi
     */
    protected function handleSuccessfulPayment(array $paymentData)
    {
        try {
            // 1. Récupérer la souscription depuis les métadonnées du paiement
            // Vous devez passer subscription_id lors de la création du paiement
            $subscriptionId = $paymentData['metadata']['subscription_id'] ??
                             $paymentData['metadata'] ??
                             null;

            if (! $subscriptionId) {
                Log::error('Subscription ID manquant dans le paiement', $paymentData);

                return response()->json(['error' => 'Subscription ID manquant'], 400);
            }

            $subscription = Subscription::findOrFail($subscriptionId);

            // 2. Marquer la souscription comme payée
            $this->subscriptionService->markAsPaid($subscription, [
                'payment_method' => $paymentData['payment_method'] ?? 'unknown',
                'transaction_id' => $paymentData['id'] ?? null,
                'amount' => $paymentData['amount'] ?? $subscription->plan->price,
            ]);

            Log::info('Paiement traité avec succès', [
                'subscription_id' => $subscription->id,
                'transaction_id' => $paymentData['id'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Paiement traité avec succès',
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur traitement paiement réussi', [
                'error' => $e->getMessage(),
                'payment_data' => $paymentData,
            ]);

            return response()->json([
                'error' => 'Erreur de traitement',
            ], 500);
        }
    }

    /**
     * Traiter un paiement échoué
     */
    protected function handleFailedPayment(array $paymentData)
    {
        try {
            $subscriptionId = $paymentData['metadata']['subscription_id'] ??
                             $paymentData['metadata'] ??
                             null;

            if ($subscriptionId) {
                $subscription = Subscription::find($subscriptionId);

                if ($subscription) {
                    // Marquer la facture comme échouée
                    $subscription->invoice?->markAsFailed();

                    // Optionnel: Annuler la souscription après X tentatives
                    // $this->subscriptionService->cancel($subscription, 'Paiement échoué');
                }
            }

            Log::warning('Paiement échoué', $paymentData);

            return response()->json([
                'success' => true,
                'message' => 'Paiement échoué traité',
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur traitement paiement échoué', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Erreur'], 500);
        }
    }

    /**
     * Traiter une annulation de souscription
     */
    protected function handleSubscriptionCanceled(array $subscriptionData)
    {
        // Logique si l'annulation vient du provider (ex: impayés récurrents)
        Log::info('Souscription annulée par le provider', $subscriptionData);

        return response()->json([
            'success' => true,
            'message' => 'Annulation traitée',
        ]);
    }
}

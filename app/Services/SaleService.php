<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SaleService
{
    /**
     * Créer une nouvelle vente
     */
    public function createSale(array $data, int $userId): Sale
    {
        return DB::transaction(function () use ($data, $userId) {
            // Vérifier que le wallet appartient à l'utilisateur
            $wallet = Wallet::where('id', $data['wallet_id'])
                ->where('user_id', $userId)
                ->firstOrFail();

            // Vérifier la disponibilité des actions (optionnel)
            $this->validateStockAvailability($wallet, $data['quantite']);

            // Créer la vente
            $sale = Sale::create([
                'wallet_id' => $data['wallet_id'],
                'user_id' => $userId,
                'quantite' => $data['quantite'],
                'prix_par_action' => $data['prix_par_action'],
                'montant_vente' => $data['montant_vente'] ?? ($data['quantite'] * $data['prix_par_action']),
                'comment' => $data['comment'] ?? null,
            ]);

            // Mettre à jour le wallet si nécessaire
            $this->updateWalletAfterSale($wallet, $sale);

            return $sale->fresh(['wallet', 'user']);
        });
    }

    /**
     * Récupérer les ventes d'un utilisateur
     */
    public function getUserSales(int $userId, ?int $walletId = null): Collection
    {
        $query = Sale::with(['wallet', 'user'])
            ->forUser($userId)
            ->recent();

        if ($walletId) {
            $query->forWallet($walletId);
        }

        return $query->get();
    }

    /**
     * Récupérer une vente spécifique
     */
    public function getSaleByKey(string $key, int $userId): ?Sale
    {
        return Sale::with(['wallet', 'user'])
            ->where('key', $key)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Supprimer une vente
     */
    public function deleteSale(string $key, int $userId): bool
    {
        return DB::transaction(function () use ($key, $userId) {
            $sale = $this->getSaleByKey($key, $userId);

            if (!$sale) {
                return false;
            }

            // Restaurer le stock dans le wallet si nécessaire
            $this->restoreWalletAfterSaleDelete($sale);

            return $sale->delete();
        });
    }

    /**
     * Vérifier la disponibilité des actions
     */
    protected function validateStockAvailability(Wallet $wallet, int $quantite): void
    {
        // Implémenter la logique selon votre besoin
        // Exemple: vérifier que le wallet a assez d'actions à vendre

        // if ($wallet->total_shares < $quantite) {
        //     throw new \Exception('Stock insuffisant dans le portefeuille.');
        // }
    }

    /**
     * Mettre à jour le wallet après une vente
     */
    protected function updateWalletAfterSale(Wallet $wallet, Sale $sale): void
    {
        // Implémenter selon votre logique métier
        // Exemple: déduire les actions vendues du wallet

        // $wallet->decrement('total_shares', $sale->quantite);
        // $wallet->increment('total_sales', $sale->montant_vente);
    }

    /**
     * Restaurer le wallet après suppression d'une vente
     */
    protected function restoreWalletAfterSaleDelete(Sale $sale): void
    {
        // Implémenter la logique inverse

        // $sale->wallet->increment('total_shares', $sale->quantite);
        // $sale->wallet->decrement('total_sales', $sale->montant_vente);
    }

    /**
     * Calculer les statistiques de vente
     */
    public function getSalesStatistics(int $userId, ?int $walletId = null): array
    {
        $query = Sale::forUser($userId);

        if ($walletId) {
            $query->forWallet($walletId);
        }

        return [
            'total_sales' => $query->count(),
            'total_amount' => $query->sum('montant_vente'),
            'total_shares_sold' => $query->sum('quantite'),
            'average_price' => $query->avg('prix_par_action'),
        ];
    }
}

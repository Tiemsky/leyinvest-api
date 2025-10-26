<?php

namespace App\Services;

use App\Models\Action;
use Illuminate\Support\Collection;

class TopFlopService {
  /**
     * Récupère le top 5 des actions par variation
     */
    public function getTop(int $limit = 5): Collection
    {
        return Action::query()
            ->orderByDesc('variation')
            ->limit($limit)
            ->get();
    }

    /**
     * Récupère le flop 5 des actions par variation
     */
    public function getFlop(int $limit = 5): Collection
    {
        return Action::query()
            ->orderBy('variation')
            ->limit($limit)
            ->get();
    }
}

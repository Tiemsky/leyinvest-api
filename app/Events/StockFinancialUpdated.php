<?php

namespace App\Events;

use App\Models\StockFinancial;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event déclenché lors de la création/modification/suppression d'un StockFinancial
 *
 * Permet à d'autres parties de l'application de réagir
 * aux changements de données financières
 */
class StockFinancialUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Le StockFinancial concerné
     */
    public StockFinancial $stockFinancial;

    /**
     * Type d'événement: 'created', 'updated', 'deleted'
     */
    public string $eventType;

    /**
     * Create a new event instance.
     */
    public function __construct(StockFinancial $stockFinancial, string $eventType)
    {
        $this->stockFinancial = $stockFinancial;
        $this->eventType = $eventType;
    }

    /**
     * Get the channels the event should broadcast on (optionnel).
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    // public function broadcastOn(): array
    // {
    //     return [
    //         new PrivateChannel('financial-updates'),
    //     ];
    // }
}

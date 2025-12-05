<?php

namespace App\Events;

use App\Models\StockFinancial;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockFinancialUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public StockFinancial $stockFinancial;

    public function __construct(StockFinancial $stockFinancial)
    {
        $this->stockFinancial = $stockFinancial;
    }
}

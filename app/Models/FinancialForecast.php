<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialForecast extends Model
{
    protected $guarded = [];

    /**
     *RENDEMENT NET PREVISIONNEL
     * Calculé à la volée : (DNPA prévisionnel / cours clôture) X 100
     */
    public function getRendementNetAttribute(){
        $cours = $this->action->cours_cloture;
        if (!$cours || $cours == 0) return 0;
        // ((DNPA prévisionnel) / cours clôture) X 100
        return ($this->dnpa_previsionnel / $cours) * 100;
    }
}

<?php

namespace App\Support;

class BrvmMapping
{
    /**
     * Source unique de vérité pour le mapping Symbole -> Secteurs
     */
    public static function actionSectorMap(): array
    {
        return [
            'NTLC' => ['Consommation de base', 'Biens de consommation'],
            'PALC' => ['Consommation de base', 'Agro Industrie'],
            'SCRC' => ['Consommation de base', 'Agro Industrie'],
            'SICC' => ['Consommation de base', 'Agro Industrie'],
            'SLBC' => ['Consommation de base', 'Biens de consommation'],
            'SOGC' => ['Consommation de base', 'Agro Industrie'],
            'SPHC' => ['Consommation de base', 'Agro Industrie'],
            'STBC' => ['Consommation de base', 'Biens de consommation'],
            'UNLC' => ['Consommation de base', 'Biens de consommation'],
            'ABJC' => ['Consommation discrétionnaire', 'Consommation discrétionnaire '],
            'BNBC' => ['Consommation discrétionnaire', 'BTP'],
            'CFAC' => ['Consommation discrétionnaire', 'Automobile '],
            'LNBB' => ['Consommation discrétionnaire', 'Consommation discrétionnaire '],
            'NEIC' => ['Consommation discrétionnaire', 'Consommation discrétionnaire '],
            'PRSC' => ['Consommation discrétionnaire', 'Automobile '],
            'UNXC' => ['Consommation discrétionnaire', 'Industrie'],
            'SHEC' => ['Énergie', 'Pétrole et Energie'],
            'SMBC' => ['Énergie', 'BTP'],
            'TTLC' => ['Énergie', 'Pétrole et Energie'],
            'TTLS' => ['Énergie', 'Pétrole et Energie'],
            'CABC' => ['Industriels', 'Industrie'],
            'FTSC' => ['Industriels', 'Industrie'],
            'SDSC' => ['Industriels', 'Logistique'],
            'SEMC' => ['Industriels', 'Industrie'],
            'SIVC' => ['Industriels', 'Industrie'],
            'STAC' => ['Industriels', 'BTP'],
            'BICB' => ['Services financiers', 'Services Financiers'],
            'BICC' => ['Services financiers', 'Services Financiers'],
            'BOAB' => ['Services financiers', 'Services Financiers'],
            'BOABF' => ['Services financiers', 'Services Financiers'],
            'BOAC' => ['Services financiers', 'Services Financiers'],
            'BOAM' => ['Services financiers', 'Services Financiers'],
            'BOAN' => ['Services financiers', 'Services Financiers'],
            'BOAS' => ['Services financiers', 'Services Financiers'],
            'CBIBF' => ['Services financiers', 'Services Financiers'],
            'ECOC' => ['Services financiers', 'Services Financiers'],
            'ETIT' => ['Services financiers', 'Services Financiers'],
            'NSBC' => ['Services financiers', 'Services Financiers'],
            'ORGT' => ['Services financiers', 'Services Financiers'],
            'SAFC' => ['Services financiers', 'Services Financiers'],
            'SGBC' => ['Services financiers', 'Services Financiers'],
            'SIBC' => ['Services financiers', 'Services Financiers'],
            'CIEC' => ['Services publics', 'Services publics'],
            'SDCC' => ['Services publics', 'Services publics'],
            'ONTBF' => ['Télécommunications', 'Télécommunications'],
            'ORAC' => ['Télécommunications', 'Télécommunications'],
            'SNTS' => ['Télécommunications', 'Télécommunications'],
        ];
    }

    /**
     * Helper pour obtenir les secteurs d'un symbole spécifique
     */
    public static function getSectorsFor(string $symbole): ?array
    {
        return self::actionSectorMap()[$symbole] ?? null;
    }
}

<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScrapeFinancialNewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** * Le nom de la classe du robot de récupération (le Scraper)
     */
    protected string $classeDuRobot;

    /**
     * Création d'une nouvelle mission pour un robot précis.
     */
    public function __construct(string $nomDeLaClasseRobot)
    {
        $this->classeDuRobot = $nomDeLaClasseRobot;
    }

    /**
     * Exécution de la mission.
     */
    public function handle(): void
    {
        try {
            // 1. On prépare le robot (on instancie la classe)
            $robot = app($this->classeDuRobot);

            Log::info('🤖 Le robot ['.class_basename($this->classeDuRobot).'] commence à chercher des actualités.');

            // 2. On lance la récupération
            $robot->scrape();

            Log::info('✅ Mission réussie pour le robot : '.class_basename($this->classeDuRobot));

        } catch (\Exception $erreur) {
            // En cas de problème (site en panne, etc.), on enregistre l'erreur
            Log::error('❌ Échec du robot ['.class_basename($this->classeDuRobot).'] : '.$erreur->getMessage());

            // On peut dire à Laravel de retenter plus tard
            $this->fail($erreur);
        }
    }
}

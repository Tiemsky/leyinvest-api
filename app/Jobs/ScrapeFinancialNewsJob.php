<?php

namespace App\Jobs;

use App\Models\FinancialNews;
use App\Services\Scrapers\BrvmScraper;
use App\Services\Scrapers\RichBourseScraper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class ScrapeFinancialNewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        \Log::info('🔍 Starting financial news scraping...');

        $scrapers = [
            'BrvmScraper' => new BrvmScraper(),
            'RichBourseScraper' => new RichBourseScraper(),
        ];

        foreach ($scrapers as $name => $scraper) {
            try {
                \Log::info("➡️ Running {$name}...");

                $items = $scraper->scrape();

                \Log::info("{$name} found " . count($items) . " items");

                foreach ($items as $item) {
                    $this->saveFinancialNews($item, $name);
                }

            } catch (\Exception $e) {
                \Log::error("❌ {$name} failed", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        \Log::info('✅ Scraping completed.');
    }

    /**
     * Sauvegarde ou met à jour une actualité financière
     */
    private function saveFinancialNews(array $item, string $scraperName): void
    {
        try {
            // Validation stricte des données
            $validated = $this->validateItem($item);

            if (!$validated) {
                \Log::warning("⚠️ Invalid item skipped from {$scraperName}", ['item' => $item]);
                return;
            }

            // Générer une clé unique
            $key = $this->generateKey($validated);

            // Upsert (update ou insert)
            FinancialNews::updateOrCreate(
                ['key' => $key],
                [
                    'company'      => $validated['company'],
                    'title'        => $validated['title'],
                    'pdf_url'      => $validated['pdf_url'],
                    'published_at' => $validated['published_at'],
                    'source'       => $validated['source'],
                ]
            );

            \Log::debug("✅ Saved: {$validated['company']} - {$validated['title']}");

        } catch (\Exception $e) {
            \Log::error('❌ Failed to save item', [
                'item' => $item,
                'scraper' => $scraperName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Valide les données d'un item
     */
    private function validateItem(array $item): ?array
    {
        // Champs obligatoires
        $required = ['title', 'pdf_url', 'published_at', 'source'];

        foreach ($required as $field) {
            if (empty($item[$field])) {
                \Log::warning("⚠️ Missing required field: {$field}", ['item' => $item]);
                return null;
            }
        }

        // Valider l'URL du PDF
        if (!filter_var($item['pdf_url'], FILTER_VALIDATE_URL)) {
            \Log::warning("⚠️ Invalid PDF URL: {$item['pdf_url']}");
            return null;
        }

        // Valider la date
        try {
            $date = \Carbon\Carbon::parse($item['published_at']);
        } catch (\Exception $e) {
            \Log::warning("⚠️ Invalid date: {$item['published_at']}");
            return null;
        }

        return [
            'company'      => $item['company'] ?? null,
            'title'        => trim($item['title']),
            'pdf_url'      => trim($item['pdf_url']),
            'published_at' => $date->toDateString(),
            'source'       => $item['source'],
        ];
    }

    /**
     * Génère une clé unique pour un item
     */
    private function generateKey(array $item): string
    {
        $raw = implode('|', [
            $item['source'],
            $item['company'] ?? '',
            $item['title'],
            $item['published_at']
        ]);

        return 'fin_' . Str::random(10);
    }
}

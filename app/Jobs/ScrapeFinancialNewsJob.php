<?php

namespace App\Jobs;

use App\Models\FinancialNews;
use App\Services\Scrapers\BaseScraper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ScrapeFinancialNewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $scraperClass;

    // Nombre de tentatives en cas d'échec (évite les boucles infinies)
    public $tries = 3;

    public function __construct(string $scraperClass)
    {
        $this->scraperClass = $scraperClass;
    }

    public function handle(): void
    {
        if (!class_exists($this->scraperClass)) return;

        $scraperName = class_basename($this->scraperClass);
        \Log::info("🚀 Job Started: {$scraperName}");

        try {
            /** @var BaseScraper $scraper */
            $scraper = new $this->scraperClass();
            $items = $scraper->scrape();

            foreach ($items as $item) {
                $this->saveFinancialNews($item, $scraperName);
            }
        } catch (\Exception $e) {
            \Log::error("❌ Critical Job Error ({$scraperName}): " . $e->getMessage());
            $this->fail($e);
        }
    }

    private function saveFinancialNews(array $item, string $scraperName): void
    {
        // 1. Validation : URL valide OU Fichier local valide
        $url = $item['pdf_url'] ?? '';
        $isRemoteUrl = filter_var($url, FILTER_VALIDATE_URL);
        $isLocalFile = !$isRemoteUrl && Storage::disk('local')->exists($url);

        if (!$isRemoteUrl && !$isLocalFile) {
            \Log::warning("⚠️ Skipped invalid PDF source: {$url}");
            return;
        }

        // 2. Génération Clé Unique
        $key = 'fin_' . md5(($item['source'] ?? '') . ($item['title'] ?? '') . ($item['published_at'] ?? ''));

        try {
            FinancialNews::updateOrCreate(
                ['key' => $key],
                [
                    'company'      => $item['company'] ?? null,
                    'title'        => trim($item['title']),
                    'pdf_url'      => $url,
                    'published_at' => $item['published_at'],
                    'source'       => $item['source'] ?? $scraperName,
                ]
            );
        } catch (\Exception $e) {
            \Log::error("❌ DB Save Error: " . $e->getMessage());
        }
    }
}

<?php

namespace App\Services\Scrapers;

use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Str;

class RichBourseScraper extends BaseScraper
{
    public function scrape(): array
    {
        $results = [];
        $baseUrl = 'https://www.richbourse.com';
        $listUrl = $baseUrl . '/common/actualite-categorie/index/etats-financiers';

        $crawler = $this->fetchCrawler($listUrl);

        if (!$crawler) {
            \Log::warning('❌ RichBourse: Failed to fetch main page');
            return $results;
        }

        // Cibler les lignes impaires/paires contenant les annonces
        $items = $crawler->filter('.ligne_impaire, .ligne_paire');
        \Log::info("🔍 RichBourse: Found {$items->count()} potential items");

        $itemCount = 0;
        foreach ($items as $item) {
            try {
                $itemCrawler = new Crawler($item, $listUrl);

                // Date dans la première colonne
                $dateNode = $itemCrawler->filter('.col-xs-4, .col-md-3, .col-lg-2');
                if (!$dateNode->count()) continue;

                $dateStr = trim($dateNode->first()->text());
                $date = $this->parseDate($dateStr);
                if (!$date || !$this->isWithinWindow($date, 14)) continue;

                // Lien vers la page de détail
                $linkNode = $itemCrawler->filter('a');
                if (!$linkNode->count()) continue;

                $link = $linkNode->first();
                $detailPath = $link->attr('href');
                if (!$detailPath || !str_starts_with($detailPath, '/common/actualite/details/')) {
                    continue;
                }

                $fullTitle = trim($link->text());
                [$company, $cleanTitle] = $this->extractCompanyAndTitle($fullTitle);

                // 🔑 Générer l'URL PDF à partir du slug
                $pdfPath = str_replace('/details/', '/afficher-fichier/', $detailPath);
                $pdfUrl = $baseUrl . $pdfPath;

                $results[] = [
                    'company'      => $company,
                    'title'        => $cleanTitle,
                    'pdf_url'      => $pdfUrl,
                    'published_at' => $date->toDateString(),
                    'source'       => 'richbourse_etats_financiers',
                ];

                $itemCount++;
                \Log::info("✅ Added: {$company} - {$cleanTitle}");

            } catch (\Exception $e) {
                \Log::error('❌ Error processing RichBourse item', [
                    'exception' => $e->getMessage(),
                    'line' => $e->getLine(),
                ]);
                continue;
            }
        }

        \Log::info("✅ RichBourseScraper completed. Found {$itemCount} items within 14-day window");
        return $results;
    }

    protected function extractCompanyAndTitle(string $fullTitle): array
    {
        if (Str::contains($fullTitle, ' : ')) {
            [$company, $title] = explode(' : ', $fullTitle, 2);
            return [trim($company), trim($title)];
        }
        return [null, $fullTitle];
    }
}

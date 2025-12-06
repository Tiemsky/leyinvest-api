<?php

namespace App\Services\Scrapers;

use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Exception;

class RichBourseScraper extends BaseScraper
{
    protected $disk = 'local';

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

        $items = $crawler->filter('.ligne_impaire, .ligne_paire');
        \Log::info("🔍 RichBourse: Found {$items->count()} potential items");

        foreach ($items as $item) {
            try {
                $itemCrawler = new Crawler($item, $listUrl);

                // 1. Date
                $dateNode = $itemCrawler->filter('.col-xs-4, .col-md-3, .col-lg-2');
                if (!$dateNode->count()) continue;
                $date = $this->parseDate(trim($dateNode->first()->text()));

                if (!$date || !$this->isWithinWindow($date, 14)) continue;

                // 2. Lien & Titre
                $linkNode = $itemCrawler->filter('a');
                if (!$linkNode->count()) continue;

                $detailPath = $linkNode->attr('href');
                if (!$detailPath || !str_starts_with($detailPath, '/common/actualite/details/')) continue;

                [$company, $cleanTitle] = $this->extractCompanyAndTitle(trim($linkNode->text()));

                // 3. Gestion Intelligente du PDF
                $pdfPath = str_replace('/details/', '/afficher-fichier/', $detailPath);
                $pdfUrl = $baseUrl . $pdfPath;

                // OPTIMISATION : Nom de fichier unique basé sur le contenu (Hash)
                // Cela empêche d'avoir 5 copies du même fichier si le script tourne 5 fois
                $uniqueHash = md5($company . $cleanTitle . $date->toDateString());
                $fileName = Str::slug($company . '-' . $cleanTitle) . '-' . $uniqueHash . '.pdf';

                // On ne télécharge QUE si le fichier n'existe pas déjà
                $filePath = $this->downloadAndStorePdf($pdfUrl, $fileName);

                if (!$filePath) continue;

                $results[] = [
                    'company'      => $company,
                    'title'        => $cleanTitle,
                    'pdf_url'      => $filePath, // On passe le chemin local
                    'published_at' => $date->toDateString(),
                    'source'       => 'richbourse_etats_financiers',
                ];

                \Log::info("✅ Processed: {$company} - {$cleanTitle}");

            } catch (Exception $e) {
                \Log::error('❌ Error processing item', ['msg' => $e->getMessage()]);
                continue;
            }
        }

        return $results;
    }

    protected function downloadAndStorePdf(string $url, string $fileName): ?string
    {
        $storageDirectory = 'richbourse/etats-financiers';
        $fullPath = $storageDirectory . '/' . $fileName;

        // 🛑 STOP DOUBLONS : Si le fichier existe déjà, on retourne le chemin sans re-télécharger
        if (Storage::disk($this->disk)->exists($fullPath)) {
            \Log::debug("📂 File already exists, skipping download: {$fileName}");
            return $fullPath;
        }

        try {
            $response = Http::timeout(45)->get($url);
            if ($response->successful()) {
                Storage::disk($this->disk)->put($fullPath, $response->body());
                \Log::info("💾 Downloaded new PDF: {$fileName}");
                return $fullPath;
            }
        } catch (Exception $e) {
            \Log::error("❌ Download failed: {$url}");
        }

        return null;
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

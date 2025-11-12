<?php

namespace App\Services\Scrapers;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Illuminate\Support\Facades\Storage;

class BrvmBaseScraper
{
    protected $client;

    public function __construct()
    {
        // Mêmes options que BaseScraper mais optimisées pour BRVM
        $options = [
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
                'Referer' => 'https://www.brvm.org/fr',
                'Cache-Control' => 'max-age=0',
            ],
        ];

        if (app()->environment('local')) {
            $options['verify_peer'] = false;
            $options['verify_host'] = false;
        }

        $this->client = HttpClient::create($options);
    }

    /**
     * Récupère la page avec une session active
     */
    public function fetchPage(string $url): ?string
    {
        \Log::debug("🌐 BRVM: Fetching URL: {$url}");

        try {
            // Visite de la homepage d'abord si ce n'est pas déjà fait
            static $sessionInitialized = false;
            if (!$sessionInitialized) {
                $this->client->request('GET', 'https://www.brvm.org/fr');
                usleep(1000000);
                $sessionInitialized = true;
            }

            $response = $this->client->request('GET', $url);
            if ($response->getStatusCode() !== 200) {
                \Log::warning("⚠️ BRVM HTTP {$response->getStatusCode()} for {$url}");
                return null;
            }

            $html = $response->getContent();
            \Log::debug("✅ BRVM: Received " . strlen($html) . " bytes");

            return $html;
        } catch (\Exception $e) {
            \Log::error("❌ BRVM fetch error: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Parse le HTML pour extraire le tableau principal
     */
    public function parseTable(string $html, string $url): array
    {
        $crawler = new Crawler($html, $url);

        // ✅ Cibler la section principale comme dans FastAPI
        $mainSection = $crawler->filterXPath('//section[@id="block-system-main"]');

        if (!$mainSection->count()) {
            \Log::warning("⚠️ BRVM: #block-system-main non trouvé");
            // Fallback: utiliser toute la page
            $mainSection = $crawler;
        }

        // ✅ Extraire le tableau principal
        $table = $mainSection->filter('table.views-table');
        if (!$table->count()) {
            \Log::warning("⚠️ BRVM: Aucun tableau trouvé");
            return [];
        }

        // ✅ Extraire les lignes avec ou sans <tbody>
        $rows = $table->filter('tbody tr');
        if (!$rows->count()) {
            // Fallback: toutes les lignes sauf celles contenant <th>
            $allRows = $table->filter('tr');
            $rows = $allRows->reduce(function (Crawler $row) {
                return !$row->filter('th')->count();
            });
        }

        \Log::debug("✅ BRVM: Found {$rows->count()} rows in table");

        $results = [];
        foreach ($rows as $row) {
            $rowCrawler = new Crawler($row, $url);

            // Colonne Date
            $dateNode = $rowCrawler->filter('td.views-field-field-date-annonce span.date-display-single');
            if (!$dateNode->count()) continue;
            $dateStr = trim($dateNode->text());

            // Colonne Société
            $companyNode = $rowCrawler->filter('td.views-field-og-group-ref');
            $company = $companyNode->count() ? trim($companyNode->text()) : null;

            // Colonne Titre
            $titleNode = $rowCrawler->filter('td.views-field-title');
            if (!$titleNode->count()) continue;
            $title = trim($titleNode->text());

            // Lien PDF
            $linkNode = $rowCrawler->filter('td.views-field-field-fichier-annonce a.btn-download');
            if (!$linkNode->count()) continue;
            $pdfUrl = trim($linkNode->attr('href'));
            if (empty($pdfUrl) || !str_starts_with($pdfUrl, 'http')) {
                $pdfUrl = 'https://www.brvm.org' . ltrim($pdfUrl, '/');
            }

            $results[] = compact('dateStr', 'company', 'title', 'pdfUrl');
        }

        return $results;
    }
}

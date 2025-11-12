<?php

namespace App\Services\Scrapers;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class BrvmScraper extends BaseScraper
{
    private const SOURCES = [
        'convocations-assemblees-generales' => 'brvm_convocations',
        'projets-de-resolution'             => 'brvm_projets_resolution',
        'notations-financieres'             => 'brvm_notations',
        'franchissements-de-seuil'          => 'brvm_seuils',
        'changements-de-dirigeants'         => 'brvm_dirigeants',
        'communiques'                       => 'brvm_communiques',
    ];

    /**
     * Client dédié à BRVM avec gestion de session
     */
    private function getBrvmClient(): \Symfony\Contracts\HttpClient\HttpClientInterface
    {
        $options = [
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
                'Referer' => 'https://www.brvm.org/fr',
                'Upgrade-Insecure-Requests' => '1',
            ],
        ];

        if (app()->environment('local')) {
            $options['verify_peer'] = false;
            $options['verify_host'] = false;
        }

        return HttpClient::create($options);
    }

    private function fetchBrvmPage(string $url): ?string
    {
        $client = $this->getBrvmClient();

        // Initialiser la session
        static $sessionInit = false;
        if (!$sessionInit) {
            $client->request('GET', 'https://www.brvm.org/fr');
            usleep(1000000);
            $sessionInit = true;
        }

        try {
            \Log::debug("🌐 BRVM: Fetching URL: {$url}");
            $response = $client->request('GET', $url);
            if ($response->getStatusCode() === 200) {
                $html = $response->getContent();
                \Log::debug("✅ BRVM: Received " . strlen($html) . " bytes");
                return $html;
            }
        } catch (\Exception $e) {
            \Log::error("❌ BRVM fetch error: {$e->getMessage()}");
        }

        return null;
    }

    public function scrape(): array
    {
        $results = [];

        foreach (self::SOURCES as $path => $sourceKey) {
            $url = 'https://www.brvm.org/fr/emetteurs/type-annonces/' . $path;
            $html = $this->fetchBrvmPage($url);

            if (!$html) {
                \Log::warning("⚠️ BRVM: Failed to fetch {$path}");
                continue;
            }

            // Utiliser Crawler sur le HTML récupéré
            $crawler = new Crawler($html, $url);

            // Cibler exclusivement le tableau dans #block-system-main
            $mainSection = $crawler->filterXPath('//section[@id="block-system-main"]');
            if (!$mainSection->count()) {
                \Log::warning("⚠️ BRVM: #block-system-main not found in {$path}");
                continue;
            }

            // Extraire le tableau principal
            $table = $mainSection->filter('table.views-table');
            if (!$table->count()) {
                \Log::warning("⚠️ BRVM: No table found in {$path}");
                continue;
            }

            // Extraire les lignes (avec ou sans tbody)
            $rows = $table->filter('tbody tr');
            if (!$rows->count()) {
                // Fallback: toutes les <tr> sauf celles avec <th>
                $allRows = $table->filter('tr');
                $rows = $allRows->reduce(function (Crawler $row) {
                    return !$row->filter('th')->count();
                });
            }

            \Log::debug("Found {$rows->count()} rows in {$path}");

            foreach ($rows as $row) {
                try {
                    $rowCrawler = new Crawler($row, $url);

                    // Date
                    $dateNode = $rowCrawler->filter('td.views-field-field-date-annonce span.date-display-single');
                    if (!$dateNode->count()) continue;

                    $dateStr = trim($dateNode->text());
                    $date = $this->parseDate($dateStr);
                    if (!$date || !$this->isWithinWindow($date, 14)) continue;

                    // Société
                    $companyNode = $rowCrawler->filter('td.views-field-og-group-ref');
                    $company = $companyNode->count() ? trim($companyNode->text()) : null;

                    // Titre
                    $titleNode = $rowCrawler->filter('td.views-field-title');
                    if (!$titleNode->count()) continue;
                    $title = trim($titleNode->text());

                    // Lien PDF
                    $linkNode = $rowCrawler->filter('td.views-field-field-fichier-annonce a.btn-download');
                    if (!$linkNode->count()) {
                        \Log::warning("⚠️ BRVM: No PDF link for: {$title}");
                        continue;
                    }

                    $pdfUrl = trim($linkNode->attr('href'));
                    if (empty($pdfUrl)) continue;

                    if (!str_starts_with($pdfUrl, 'http')) {
                        $pdfUrl = 'https://www.brvm.org' . ltrim($pdfUrl, '/');
                    }

                    $results[] = [
                        'company'      => $company,
                        'title'        => $title,
                        'pdf_url'      => $pdfUrl,
                        'published_at' => $date->toDateString(),
                        'source'       => $sourceKey,
                    ];

                    \Log::debug("✅ BRVM: Added {$company} - {$title}");

                } catch (\Exception $e) {
                    \Log::error('❌ BRVM row error', [
                        'exception' => $e->getMessage(),
                        'url' => $url,
                    ]);
                    continue;
                }
            }
        }

        return $results;
    }
}

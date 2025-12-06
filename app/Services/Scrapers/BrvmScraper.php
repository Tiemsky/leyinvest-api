<?php

namespace App\Services\Scrapers;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str; // Pour la fonction Str::slug
use Exception; // Utiliser la classe de base

class BrvmScraper extends BaseScraper
{
    // Définir le disque de stockage (doit être configuré dans config/filesystems.php)
    protected $disk = 'local';
    protected $storageDirectory = 'brvm/annonces';

    private const SOURCES = [
        'convocations-assemblees-generales' => 'brvm_convocations',
        'projets-de-resolution'             => 'brvm_projets_resolution',
        'notations-financieres'              => 'brvm_notations',
        'franchissements-de-seuil'          => 'brvm_seuils',
        'changements-de-dirigeants'          => 'brvm_dirigeants',
        'communiques'                       => 'brvm_communiques',
    ];

    /**
     * Client dédié à BRVM avec gestion de session
     */
    private function getBrvmClient(): HttpClientInterface
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

        // Vérification de l'environnement (similaire à votre code original)
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
            try {
                // Requête d'initialisation de session (sans vérification du statut)
                $client->request('GET', 'https://www.brvm.org/fr');
                usleep(1000000); // Pause d'une seconde pour simuler un comportement humain
            } catch (Exception $e) {
                 \Log::warning("⚠️ BRVM session init failed: {$e->getMessage()}");
            }
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
        } catch (Exception $e) {
            \Log::error("❌ BRVM fetch error: {$e->getMessage()}");
        }

        return null;
    }

    // 🚨 NOUVELLE MÉTHODE : Télécharge et stocke le PDF de manière déterministe
    protected function downloadAndStorePdf(string $url, string $company, string $title, string $dateString): ?string
    {
        // 1. Génération du Nom de Fichier Unique (Clé de Déduplication)
        // Utilise le MD5 pour créer un nom de fichier déterministe, basé sur les métadonnées.
        $uniqueHash = md5($company . $title . $dateString);
        $fileName = Str::slug($company . '-' . $title) . '-' . $uniqueHash . '.pdf';
        $fullPath = $this->storageDirectory . '/' . $fileName;

        // 2. Vérification d'Existence (Stop aux doublons)
        if (Storage::disk($this->disk)->exists($fullPath)) {
            \Log::debug("📂 File already exists on disk, skipping download: {$fileName}");
            return $fullPath;
        }

        // 3. Téléchargement et Stockage
        try {
            $response = $this->getBrvmClient()->request('GET', $url);

            if ($response->getStatusCode() === 200) {
                // Stockage du contenu brut
                Storage::disk($this->disk)->put($fullPath, $response->getContent());
                \Log::info("💾 PDF stored successfully on '{$this->disk}' disk at: {$fullPath}");
                return $fullPath;
            }
        } catch (Exception $e) {
            \Log::error("❌ BRVM PDF download failed for {$url}: {$e->getMessage()}");
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

            $crawler = new Crawler($html, $url);
            $mainSection = $crawler->filterXPath('//section[@id="block-system-main"]');

            if (!$mainSection->count()) {
                \Log::warning("⚠️ BRVM: #block-system-main not found in {$path}");
                continue;
            }

            $table = $mainSection->filter('table.views-table');
            if (!$table->count()) {
                \Log::warning("⚠️ BRVM: No table found in {$path}");
                continue;
            }

            // Logique d'extraction des lignes
            $rows = $table->filter('tbody tr');
            if (!$rows->count()) {
                $rows = $table->filter('tr')->reduce(fn (Crawler $row) => !$row->filter('th')->count());
            }

            \Log::debug("Found {$rows->count()} rows in {$path}");

            foreach ($rows as $row) {
                try {
                    $rowCrawler = new Crawler($row, $url);

                    // 1. Date
                    $dateNode = $rowCrawler->filter('td.views-field-field-date-annonce span.date-display-single');
                    if (!$dateNode->count()) continue;
                    $dateStr = trim($dateNode->text());
                    $date = $this->parseDate($dateStr);
                    if (!$date || !$this->isWithinWindow($date, 14)) continue;

                    // 2. Société
                    $companyNode = $rowCrawler->filter('td.views-field-og-group-ref');
                    $company = $companyNode->count() ? trim($companyNode->text()) : 'N/A';

                    // 3. Titre
                    $titleNode = $rowCrawler->filter('td.views-field-title');
                    if (!$titleNode->count()) continue;
                    $title = trim($titleNode->text());

                    // 4. Lien PDF
                    $linkNode = $rowCrawler->filter('td.views-field-field-fichier-annonce a.btn-download');
                    if (!$linkNode->count()) continue;

                    $pdfUrl = trim($linkNode->attr('href'));
                    if (empty($pdfUrl)) continue;

                    // Reconstruire l'URL absolue si nécessaire
                    if (!str_starts_with($pdfUrl, 'http')) {
                        $pdfUrl = 'https://www.brvm.org' . ltrim($pdfUrl, '/');
                    }

                    // 🚨 NOUVEAU : Télécharger et obtenir le chemin local
                    $localPath = $this->downloadAndStorePdf($pdfUrl, $company, $title, $date->toDateString());

                    if (!$localPath) {
                        \Log::error("❌ Failed to process or store PDF for: {$title}");
                        continue;
                    }

                    $results[] = [
                        'company'      => $company,
                        'title'        => $title,
                        'pdf_url'      => $localPath, // 🚨 Le chemin est maintenant le chemin local !
                        'published_at' => $date->toDateString(),
                        'source'       => $sourceKey,
                    ];

                    \Log::debug("✅ BRVM: Added {$company} - {$title}");

                } catch (Exception $e) {
                    \Log::error('❌ BRVM row processing error', [
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

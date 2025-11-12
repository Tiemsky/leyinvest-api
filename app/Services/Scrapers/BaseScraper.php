<?php

namespace App\Services\Scrapers;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

abstract class BaseScraper
{
    protected $client;
    protected $debugMode = false;

    public function __construct()
    {
        $options = [
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language' => 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
                'Cache-Control' => 'max-age=0',
                // 🔥 Supprimé les headers Sec-Fetch-* (inutiles/encombrants en backend)
            ],
        ];

        // Désactiver la vérification SSL en local
        if (app()->environment('local')) {
            $options['verify_peer'] = false;
            $options['verify_host'] = false;
            $this->debugMode = config('app.debug', false);
        }

        $this->client = HttpClient::create($options);
    }

    /**
     * Récupère une URL et retourne un Crawler
     */
    protected function fetchCrawler(string $url): ?Crawler
    {
        try {
            \Log::debug("🌐 Fetching URL: {$url}");

            // Ajouter un délai aléatoire pour éviter le rate limiting
            usleep(rand(500000, 1500000)); // 0.5 à 1.5 secondes

            $response = $this->client->request('GET', $url);
            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200) {
                \Log::warning("⚠️ HTTP {$statusCode} for {$url}");

                // Si 403, essayer avec un autre User-Agent
                if ($statusCode === 403) {
                    \Log::info("🔄 Retrying with different User-Agent...");
                    return $this->retryWithDifferentHeaders($url);
                }

                return null;
            }

            $html = $response->getContent();
            $htmlLength = strlen($html);

            \Log::debug("✅ Received {$htmlLength} bytes");

            // Mode debug: sauvegarder le HTML
            if ($this->debugMode) {
                $this->saveDebugHtml($url, $html);
            }

            return new Crawler($html, $url);

        } catch (ExceptionInterface $e) {
            \Log::error("❌ HTTP error for {$url}", [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        } catch (\Exception $e) {
            \Log::error("❌ Unexpected error for {$url}", [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Réessaye avec des headers différents (pour contourner le 403)
     */
    protected function retryWithDifferentHeaders(string $url): ?Crawler
    {
        try {
            $alternateClient = HttpClient::create([
                'timeout' => 30,
                'verify_peer' => false,
                'verify_host' => false,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'fr-FR,fr;q=0.9',
                    // 🔥 Correction : suppression des espaces superflus à la fin
                    'Referer' => 'https://www.richbourse.com/',
                    'DNT' => '1',
                ],
            ]);

            usleep(rand(1000000, 2000000)); // 1-2 secondes

            $response = $alternateClient->request('GET', $url);

            if ($response->getStatusCode() === 200) {
                $html = $response->getContent();
                \Log::info("✅ Retry successful!");
                return new Crawler($html, $url);
            }

        } catch (\Exception $e) {
            \Log::error("❌ Retry failed: {$e->getMessage()}");
        }

        return null;
    }

    /**
     * Sauvegarde le HTML pour debug
     */
    protected function saveDebugHtml(string $url, string $html): void
    {
        try {
            $filename = 'debug_' . md5($url) . '_' . date('YmdHis') . '.html';
            $path = 'scraper_debug/' . $filename;

            Storage::put($path, $html);

            \Log::debug("💾 HTML saved to: {$path}");
        } catch (\Exception $e) {
            \Log::warning("Failed to save debug HTML: {$e->getMessage()}");
        }
    }

    /**
     * Parse une date au format d/m/Y
     */
    protected function parseDate(?string $dateString): ?Carbon
    {
        if (!$dateString) {
            return null;
        }

        // Nettoyer la chaîne
        $dateString = trim($dateString);

        try {
            return Carbon::createFromFormat('d/m/Y', $dateString);
        } catch (\Exception $e) {
            \Log::warning("Failed to parse date: '{$dateString}'", [
                'exception' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Vérifie si la date est dans la fenêtre de jours autorisés
     */
    protected function isWithinWindow(Carbon $date, int $days): bool
    {
        $cutoffDate = Carbon::today()->subDays($days);
        return $date->gte($cutoffDate);
    }

    /**
     * Méthode abstraite à implémenter par les scrapers
     *
     * @return array<int, array{
     *     company: string|null,
     *     title: string,
     *     pdf_url: string,
     *     published_at: string,
     *     source: string
     * }>
     */
    abstract public function scrape(): array;
}

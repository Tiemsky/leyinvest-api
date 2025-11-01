<?php

namespace App\Services\Scrapers;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Illuminate\Support\Carbon;

abstract class BaseScraper
{
    protected $client;

    public function __construct()
    {
        $this->client = HttpClient::create(['timeout' => 10]);
    }

    /**
     * @return array<int, array{
     *     company: string|null,
     *     title: string,
     *     pdf_url: string,
     *     published_at: string|null,
     *     source: string
     * }>
     */
    abstract public function scrape(): array;

    /**
     * Effectue une requête HTTP et retourne un DomCrawler.
     */
    protected function fetchCrawler(string $url): ?Crawler
    {
        try {
            $response = $this->client->request('GET', $url);
            $html = $response->getContent();
            return new Crawler($html, $url);
        } catch (ExceptionInterface $e) {
            \Log::warning("HTTP error for $url", ['exception' => $e]);
            return null;
        }
    }

    protected function parseDate(?string $dateString): ?Carbon
    {
        if (!$dateString) return null;
        try {
            return Carbon::createFromFormat('d/m/Y', trim($dateString));
        } catch (\Exception $e) {
            \Log::warning("Failed to parse date: $dateString", ['exception' => $e]);
            return null;
        }
    }

    protected function isWithinWindow(Carbon $date, int $days): bool
    {
        return $date->gte(Carbon::today()->subDays($days));
    }
}

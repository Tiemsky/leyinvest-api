<?php

namespace App\Modules\FinancialNews\Scrapers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Services\Scrapers\BaseScraper;
use Symfony\Component\DomCrawler\Crawler;

class RichBourseScraper extends BaseScraper
{
    public function scrape(): array
    {
        $results = [];
        $baseUrl = 'https://www.richbourse.com';
        $listUrl = $baseUrl . '/common/actualite-categorie/index/etats-financiers';

        $crawler = $this->fetchCrawler($listUrl);
        if (!$crawler) return [];

        $items = $crawler->filter('.ligne_impaire, .ligne_paire');
        foreach ($items as $item) {
            $itemCrawler = new Crawler($item, $listUrl);

            $dateNode = $itemCrawler->filter('.col-xs-4');
            $linkNode = $itemCrawler->filter('a');

            if (!$dateNode->count() || !$linkNode->count()) continue;

            $dateStr = trim($dateNode->text());
            $date = $this->parseDate($dateStr);
            if (!$date || !$this->isWithinWindow($date, 14)) continue;

            $detailPath = $linkNode->attr('href');
            if (!$detailPath) continue;

            $detailUrl = $baseUrl . $detailPath;
            $fullTitle = trim($linkNode->text());

            [$company, $cleanTitle] = $this->extractCompanyAndTitle($fullTitle);

            // Scraping de la page détail
            $detailCrawler = $this->fetchCrawler($detailUrl);
            if (!$detailCrawler) continue;

            $pdfLink = $detailCrawler->filter('a[href*=".pdf"]')->first();
            if (!$pdfLink) continue;

            $pdfUrl = $pdfLink->attr('href');
            if (!Str::startsWith($pdfUrl, ['http://', 'https://'])) {
                $pdfUrl = rtrim($baseUrl, '/') . '/' . ltrim($pdfUrl, '/');
            }

            $results[] = [
                'company'      => $company,
                'title'        => $cleanTitle,
                'pdf_url'      => trim($pdfUrl),
                'published_at' => $date->toDateString(),
                'source'       => 'richbourse_etats_financiers',
            ];
        }

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

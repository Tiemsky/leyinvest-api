<?php

namespace App\Models\Scrapers;

use Illuminate\Support\Facades\Log;
use App\Services\Scrapers\BaseScraper;
use Symfony\Component\DomCrawler\Crawler;

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

    public function scrape(): array
    {
        $results = [];
        foreach (self::SOURCES as $path => $sourceKey) {
            $url = "https://www.brvm.org/fr/emetteurs/type-annonces/$path";
            $crawler = $this->fetchCrawler($url);

            if (!$crawler) continue;

            $rows = $crawler->filter('table.views-table tbody tr');
            foreach ($rows as $row) {
                $rowCrawler = new Crawler($row, $url);

                $dateEl = $rowCrawler->filter('td.views-field-field-date-annonce span.date-display-single');
                $companyEl = $rowCrawler->filter('td.views-field-og-group-ref');
                $titleEl = $rowCrawler->filter('td.views-field-title');
                $linkEl = $rowCrawler->filter('td.views-field-field-fichier-annonce a');

                if (!$dateEl->count() || !$companyEl->count() || !$titleEl->count() || !$linkEl->count()) {
                    continue;
                }

                $dateStr = $dateEl->text();
                $date = $this->parseDate($dateStr);
                if (!$date || !$this->isWithinWindow($date, 7)) continue;

                $results[] = [
                    'company'      => trim($companyEl->text()),
                    'title'        => trim($titleEl->text()),
                    'pdf_url'      => trim($linkEl->attr('href')),
                    'published_at' => $date->toDateString(),
                    'source'       => $sourceKey,
                ];
            }
        }

        return $results;
    }
}

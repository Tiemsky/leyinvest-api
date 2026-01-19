<?php

namespace App\Console\Commands;

use App\Services\Scrapers\BrvmScraper;
use App\Services\Scrapers\RichBourseScraper;
use Illuminate\Console\Command;

class TestScraperCommand extends Command
{
    protected $signature = 'scraper:test {scraper?}';

    protected $description = 'Test a specific scraper without saving to database';

    public function handle()
    {
        $scraperName = $this->argument('scraper');

        $scrapers = [
            'brvm' => BrvmScraper::class,
            'richbourse' => RichBourseScraper::class,
        ];

        if ($scraperName && ! isset($scrapers[$scraperName])) {
            $this->error("Unknown scraper: {$scraperName}");
            $this->info('Available: brvm, richbourse');

            return 1;
        }

        $toTest = $scraperName
            ? [$scraperName => $scrapers[$scraperName]]
            : $scrapers;

        foreach ($toTest as $name => $class) {
            $this->info("\n🔍 Testing {$name}...");
            $this->line(str_repeat('=', 50));

            try {
                $scraper = new $class;
                $items = $scraper->scrape();

                $this->info('✅ Found '.count($items).' items');

                if (count($items) > 0) {
                    $this->table(
                        ['Company', 'Title', 'Date', 'PDF URL'],
                        array_map(function ($item) {
                            return [
                                $item['company'] ?? 'N/A',
                                \Illuminate\Support\Str::limit($item['title'], 50),
                                $item['published_at'],
                                \Illuminate\Support\Str::limit($item['pdf_url'], 60),
                            ];
                        }, array_slice($items, 0, 5))
                    );

                    if (count($items) > 5) {
                        $this->line('... and '.(count($items) - 5).' more items');
                    }
                } else {
                    $this->warn('No items found');
                }

            } catch (\Exception $e) {
                $this->error('❌ Error: '.$e->getMessage());
                $this->line($e->getTraceAsString());
            }
        }

        return 0;
    }
}

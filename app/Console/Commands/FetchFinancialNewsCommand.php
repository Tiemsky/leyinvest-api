<?php

namespace App\Console\Commands;

use App\Jobs\ScrapeFinancialNewsJob;
use App\Services\Scrapers\BrvmScraper;
use App\Services\Scrapers\RichBourseScraper;
use Illuminate\Console\Command;

class FetchFinancialNewsCommand extends Command
{
    protected $signature = 'news:fetch';
    protected $description = 'Dispatch scraping jobs for financial news services';

    public function handle(): void
    {
        $this->info('🦅 Dispatching scrapers...');

        // Liste explicite des scrapers actifs
        $scrapers = [
            BrvmScraper::class,
            RichBourseScraper::class,
        ];

        foreach ($scrapers as $scraperClass) {
            dispatch(new ScrapeFinancialNewsJob($scraperClass));
            $this->info("✅ Job dispatched for: " . class_basename($scraperClass));
        }

        $this->newLine();
    }
}

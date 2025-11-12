<?php

namespace App\Console\Commands;

use App\Jobs\ScrapeFinancialNewsJob;
use Illuminate\Console\Command;

class FetchFinancialNewsCommand extends Command
{
    protected $signature = 'news:fetch';
    protected $description = 'Scrape financial news from BRVM and RichBourse';

    public function handle(): void
    {
        set_time_limit(300); // 5 minutes
        dispatch(new ScrapeFinancialNewsJob());
        $this->info('✅ Scraping job dispatched.');
    }
}

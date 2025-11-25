<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:test-email';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        \Mail::raw('Test email from Laravel container', function ($message) {
            $message->to('tiemksy@gmail.com')
                    ->subject('Test SMTP Laravel');
        });
        $this->info('Email sent!');
    }
}

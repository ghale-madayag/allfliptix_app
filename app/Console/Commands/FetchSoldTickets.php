<?php

namespace App\Console\Commands;

use App\Jobs\FetchSoldTicketsJob;
use Illuminate\Console\Command;

class FetchSoldTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:sold-tickets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'fetch sold tickets';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        dispatch(new FetchSoldTicketsJob());
        $this->info('FetchSoldTickets dispatched.');
    }
}

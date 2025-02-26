<?php

namespace App\Console\Commands;

use App\Jobs\FetchtLastYearJob;
use Illuminate\Console\Command;

class DispatchFetchtLastYear extends Command
{
    protected $signature = 'fetch:last-year';
    protected $description = 'Fetch and sync last year';


    public function handle()
    {
        dispatch(new FetchtLastYearJob());
        $this->info('FetchLastYearJob dispatched.');
    }
}

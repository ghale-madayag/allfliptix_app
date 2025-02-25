<?php

namespace App\Console\Commands;

use App\Jobs\FetchLastYearInventoryJob;
use Illuminate\Console\Command;

class FetchLastYearInventory extends Command
{
    protected $signature = 'fetch:inventory-last-year';
    protected $description = 'Fetch and sync last year inventory';

    public function handle()
    {
        dispatch(new FetchLastYearInventoryJob());
        $this->info('FetchLastYearInventoryJob dispatched.');
    }
}

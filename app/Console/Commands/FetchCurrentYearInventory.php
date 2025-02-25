<?php

namespace App\Console\Commands;

use App\Jobs\FetchCurrentYearInventoryJob;
use Illuminate\Console\Command;

class FetchCurrentYearInventory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:inventory-current';
    protected $description = 'Fetch and sync current year inventory';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        dispatch(new FetchCurrentYearInventoryJob());
        $this->info('FetchCurrentYearInventoryJob dispatched.');
    }
}

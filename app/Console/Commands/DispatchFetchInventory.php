<?php

namespace App\Console\Commands;

use App\Jobs\FetchInventoryJob;
use Illuminate\Console\Command;

class DispatchFetchInventory extends Command
{
    protected $signature = 'fetch:inventory';
    protected $description = 'Dispatch the FetchInventoryJob to update inventory data';

    public function handle()
    {
        FetchInventoryJob::dispatch();
        $this->info('FetchInventoryJob dispatched successfully!');
    }
}

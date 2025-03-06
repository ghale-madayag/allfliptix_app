<?php

namespace App\Console\Commands;

use App\Jobs\FetchProfitJob;
use Illuminate\Console\Command;

class DispatchProfitJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:profit-job';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Profit Job';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        dispatch(new FetchProfitJob());
        $this->info('FetchProfitJob dispatched.');
    }
}

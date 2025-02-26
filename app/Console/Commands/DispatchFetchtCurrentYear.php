<?php

namespace App\Console\Commands;

use App\Jobs\FetchtCurrentYearJob;
use Illuminate\Console\Command;

class DispatchFetchtCurrentYear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:current-year';
    protected $description = 'Fetch and sync current year';


    public function handle()
    {
        dispatch(new FetchtCurrentYearJob());
        $this->info('FetchCurrentYearJob dispatched.');
    }
}

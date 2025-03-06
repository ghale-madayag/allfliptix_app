<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use App\Models\SoldInventoryTotal;
use Illuminate\Support\Facades\Log;

class FetchProfitJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $periods = [
            '7d'   => [now()->subDays(8)->setTime(16, 0, 0, 0), now()->setTime(15, 59, 59, 999)],
            '30d'  => [now()->subDays(31)->setTime(16, 0, 0, 0), now()->setTime(15, 59, 59, 999)],
            '90d'  => [now()->subDays(91)->setTime(16, 0, 0, 0), now()->setTime(15, 59, 59, 999)],
            'this_month' => [
                now()->startOfMonth()->subDay()->setTime(16, 0, 0, 0), // Start of the month, minus 1 day
                now()->setTime(15, 59, 59, 999) // End of today
            ],
        ];

        // Process 365 days in 30-day chunks
        for ($i = 0; $i < 12; $i++) {
            $start = now()->subDays(366)->addDays($i * 31)->setTime(16, 0, 0, 0);
            $end = now()->subDays(366)->addDays(($i + 1) * 31)->setTime(15, 59, 59, 999);
            $periods["365d_part_$i"] = [$start, $end];
        }

        // Add Monthly Data for Last Year and This Year
        for ($year = now()->year - 1; $year <= now()->year; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                $start = now()->setYear($year)->setMonth($month)->startOfMonth()->subDay()->setTime(16, 0, 0, 0);
                $end = now()->setYear($year)->setMonth($month)->endOfMonth()->setTime(15, 59, 59, 999);
                $periods["{$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT)] = [$start, $end];
            }
        }

        foreach ($periods as $key => [$from, $to]) {
            $response = Http::withHeaders([
                'X-Api-Token' => env('SKYBOX_AUTH_TOKEN'), 
                'X-Application-Token' => env('SKYBOX_API_TOKEN'),
                'Accept' => 'application/json',
            ])->get("https://skybox.vividseats.com/services/inventory/sold", [
                'invoiceDateFrom' => $from->toIso8601String(),
                'invoiceDateTo' => $to->toIso8601String(),
            ]);

            if ($response->successful()) {
                $data = $response->json()['soldInventoryTotals'] ?? [];
    
                $totalProfit = $data['totalProfit'] ?? 0;
                $totalProfitMargin = $data['totalProfitMargin'] ?? 0;
                $totalQuantity = $data['totalQuantity'] ?? 0;
    
                SoldInventoryTotal::updateOrCreate(
                    ['period' => $key],
                    [
                        'invoice_date_from' => $from,
                        'invoice_date_to' => $to,
                        'total_profit' => $totalProfit,
                        'total_profit_margin' => $totalProfitMargin,
                        'total_qty' => $totalQuantity, 
                    ]
                );
                
            }else{
                Log::error('Fetch Profit Job Error :' . $response->body());
            }

        }

        Log::info('Fetch Profit Job completed successfully.');
    }
}

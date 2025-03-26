<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Inventory;
use Carbon\Carbon;
use Exception;

class FetchSoldTicketsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle()
    {
        try {
            $cacheKey = 'sold_tickets_data';
            $cacheDuration = 300; // 5 minutes (300 seconds)

            // Check if cached data exists
            if (Cache::has($cacheKey)) {
                Log::info('Using cached data for sold tickets.');
                return;
            }

            // Fetch from API if not cached
            $today = Carbon::today();
            $oneDayAgo = $today->copy()->subDay();
            $threeDaysAgo = $today->copy()->subDays(3);
            $sevenDaysAgo = $today->copy()->subDays(7);
            $thirtyDaysAgo = $today->copy()->subDays(30);
            $startYear = $today->copy()->startOfYear();

            $apiToken = env('SKYBOX_API_TOKEN');
            $authToken = env('SKYBOX_AUTH_TOKEN');

            $url = 'https://skybox.vividseats.com/services/inventory/sold?invoiceDateFrom=' . $startYear->toDateString();
            
            $response = Http::withHeaders([
                'X-Api-Token' => $authToken, 
                'X-Application-Token' => $apiToken,
                'Accept' => 'application/json',
            ])->get($url);

            Log::info('Fetching new data from API.');

            if ($response->failed()) {
                Log::error('Failed to fetch data from API');
                return;
            }

            $data = $response->json();
            $events = [];

            foreach ($data['rows'] as $item) {
                $eventId = $item['eventId'];
                $quantity = $item['quantity'];
                $unitCostAverage = $item['unitCostAverage'];
                $profit = $item['profitMargin'];
                $invoiceDate = Carbon::parse($item['invoiceDate']);

                if (!isset($events[$eventId])) {
                    $events[$eventId] = [
                        'event_id' => $eventId,
                        'name' => $item['event']['name'],
                        'date' => $item['event']['date'],
                        'venue' => $item['event']['venue']['name'] ?? 'N/A',
                        'sold' => 0,
                        'profit_margin' => 0,
                        'unitCostAverage' => 0,  
                        'total' => 0,
                        'profit' => 0,
                        'profits' => [
                            '1d' => [],
                            '3d' => [],
                            '7d' => [],
                            '30d' => [],
                        ],
                    ];
                }

                $events[$eventId]['sold'] += $quantity;
                $events[$eventId]['unitCostAverage'] += $unitCostAverage;
                $totalCom = $item['total'];
                $profitCom = $item['profit'];

                $events[$eventId]['total'] += $totalCom;
                $events[$eventId]['profit'] += $profitCom;

                $events[$eventId]['profit_margin'] = ($events[$eventId]['total'] > 0) 
                    ? ($events[$eventId]['profit'] / $events[$eventId]['total']) * 100 
                    : 0;

                if ($invoiceDate->between($oneDayAgo, $today)) {
                    $events[$eventId]['profits']['1d'][] = $profit;
                }
                if ($invoiceDate->between($threeDaysAgo, $today)) {
                    $events[$eventId]['profits']['3d'][] = $profit;
                }
                if ($invoiceDate->between($sevenDaysAgo, $today)) {
                    $events[$eventId]['profits']['7d'][] = $profit;
                }
                if ($invoiceDate->between($thirtyDaysAgo, $today)) {
                    $events[$eventId]['profits']['30d'][] = $profit;
                }
            }

            $upsertData = [];
            foreach ($events as &$event) {
                foreach ($event['profits'] as $key => $profits) {
                    $event['avg_sold_' . $key] = !empty($profits) ? round(array_sum($profits) / count($profits), 2) : 0;
                }
                unset($event['profits']);

                $upsertData[] = [
                    'event_id' => $event['event_id'],
                    'name' => $event['name'],
                    'date' => $event['date'],
                    'venue' => $event['venue'],
                    'sold' => $event['sold'],
                    'qty' => $event['sold'],
                    'unit_cost'=> $event['unitCostAverage'],
                    'profit_margin' => round($event['profit_margin'], 2),
                    'avg_profit_1d' => $event['avg_sold_1d'],
                    'avg_profit_3d' => $event['avg_sold_3d'],
                    'avg_profit_7d' => $event['avg_sold_7d'],
                    'avg_profit_30d' => $event['avg_sold_30d'],
                ];
            }

            if (!empty($upsertData)) {
                Inventory::upsert($upsertData, ['event_id'], [
                    'name', 'date', 'venue', 'sold', 'qty', 'unit_cost','profit_margin',
                    'avg_profit_1d', 'avg_profit_3d', 'avg_profit_7d', 'avg_profit_30d'
                ]);
                Log::info('Inventory table updated successfully.');
            }

            // Cache data for 5 minutes
            Cache::put($cacheKey, $upsertData, $cacheDuration);
            Log::info('Cached new data for 5 minutes.');

        } catch (Exception $e) {
            Log::error("FetchSoldTicketsJob encountered an error: " . $e->getMessage());
        }
    }
}

<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Inventory;
use App\Models\SoldTicket;
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
            $today = Carbon::today();
            $oneDayAgo = $today->copy()->subDay();
            $threeDaysAgo = $today->copy()->subDays(3);
            $sevenDaysAgo = $today->copy()->subDays(7);
            $thirtyDaysAgo = $today->copy()->subDays(30);
            $startYear = $today->copy()->startOfYear();

            $apiToken = env('SKYBOX_API_TOKEN');
            $authToken = env('SKYBOX_AUTH_TOKEN');

            // Fetch data from the external API
            $url = 'https://skybox.vividseats.com/services/inventory/sold?invoiceDateFrom=' . $startYear->toDateString();
            
            $response = Http::withHeaders([
                'X-Api-Token' => $authToken, 
                'X-Application-Token' => $apiToken,
                'Accept' => 'application/json',
            ])->get($url);

            Log::info('Start syncing the data '. $response);

            if ($response->failed()) {
                // Handle error
                return response()->json(['error' => 'Failed to fetch data from API'], 500);
                Log::error(response()->json(['error' => 'Failed to fetch data from API'], 500));
            }

            $data = $response->json();

            // Process and aggregate the data
            $events = [];

            foreach ($data['rows'] as $item) {
                $eventId = $item['eventId'];
                $quantity = $item['quantity'];
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
                        'profit' => [
                            '1d' => [],
                            '3d' => [],
                            '7d' => [],
                            '30d' => [],
                        ],
                    ];
                }

                $events[$eventId]['sold'] += $quantity;
                $events[$eventId]['profit_margin'] += $profit;

                // Categorize profits based on date ranges
                if ($invoiceDate->between($oneDayAgo, $today)) {
                    $events[$eventId]['profit']['1d'][] = $profit;
                }
                if ($invoiceDate->between($threeDaysAgo, $today)) {
                    $events[$eventId]['profit']['3d'][] = $profit;
                }
                if ($invoiceDate->between($sevenDaysAgo, $today)) {
                    $events[$eventId]['profit']['7d'][] = $profit;
                }
                if ($invoiceDate->between($thirtyDaysAgo, $today)) {
                    $events[$eventId]['profit']['30d'][] = $profit;
                }
            }

            $upsertData = [];
            // Calculate average profits
            foreach ($events as &$event) {
                foreach ($event['profit'] as $key => $profits) {
                    $event['avg_sold_' . $key] = !empty($profits) ? round(array_sum($profits) / count($profits), 2) : 0;
                }
                unset($event['profit']); // Remove intermediate profits array

                $upsertData[] = [
                    'event_id' => $event['event_id'],
                    'name' => $event['name'],
                    'date' => $event['date'],
                    'venue' => $event['venue'],
                    'sold' => $event['sold'],
                    'qty' => $event['sold'],
                    'profit_margin' => $event['profit_margin'],
                    'avg_profit_1d' => $event['avg_sold_1d'],
                    'avg_profit_3d' => $event['avg_sold_3d'],
                    'avg_profit_7d' => $event['avg_sold_7d'],
                    'avg_profit_30d' =>  $event['avg_sold_30d'],
                ];
            }

            // Convert associative array to indexed array
            $inventory = array_values($events);

            // Calculate summary statistics for the current month
            $totalQtyThisMonth = array_sum(array_column($inventory, 'sold'));
            $totalProfitThisMonth = array_sum(array_column($inventory, 'profit_margin'));
            $totalProfitMarginThisMonth = $totalQtyThisMonth > 0 ? $totalProfitThisMonth / $totalQtyThisMonth : 0;

            Log::info($inventory);

            if (!empty($inventory)) {
                Inventory::upsert($upsertData, ['event_id'], 
                    [
                        'name',
                        'date',
                        'venue',
                        'sold',
                        'qty',
                        'profit_margin',
                        'avg_profit_1d',
                        'avg_profit_3d',
                        'avg_profit_7d',
                        'avg_profit_30d'
                    ]
                );
                Log::info('Inventory table updated successfully.');
            }


            } catch (Exception $e) {
                Log::error("FetchSoldTicketsJob encountered an error: " . $e->getMessage());
            }
            //throw $th;
        // try {
        //     $apiToken = env('SKYBOX_API_TOKEN');
        //     $authToken = env('SKYBOX_AUTH_TOKEN');
        //     $soldTicketsData = [];

        //     Inventory::whereYear('date', '>=', now()->subYear()->year)
        //         ->chunk(50, function ($inventoryRecords) use ($authToken, $apiToken, &$soldTicketsData) {
        //             foreach ($inventoryRecords as $inventory) {
        //                 try {
        //                     $eventId = $inventory->event_id;
        //                     $soldTicketsUrl = "https://skybox.vividseats.com/services/inventory/sold?eventId={$eventId}";

        //                     $soldResponse = Http::withHeaders([
        //                         'X-Api-Token' => $authToken, 
        //                         'X-Application-Token' => $apiToken,
        //                         'Accept' => 'application/json',
        //                     ])->get($soldTicketsUrl);

        //                     if ($soldResponse->failed()) {
        //                         Log::warning("FetchSoldTicketsJob failed for Event ID: {$eventId}");
        //                         continue;
        //                     }

        //                     $soldData = $soldResponse->json();
        //                     $soldQuantity = $soldData['soldInventoryTotals']['totalQuantity'] ?? 0;
        //                     $totalProfitMargin = $soldData['soldInventoryTotals']['totalProfitMargin'] ?? 0;

        //                     Inventory::where('event_id', $eventId)->update([
        //                         'sold' => $soldQuantity,
        //                         'profit_margin' => $totalProfitMargin,
        //                         'updated_at' => now()
        //                     ]);

        //                     foreach ($soldData['rows'] ?? [] as $soldItem) {
        //                         $soldTicketsData[] = [
        //                             'event_id' => $eventId,
        //                             'invoiceId' => $soldItem['invoiceId'] ?? 0,
        //                             'customerDisplayName' => $soldItem['customerDisplayName'] ?? 0,
        //                             'lowSeat' => $soldItem['lowSeat'] ?? 0,
        //                             'highSeat' => $soldItem['highSeat'] ?? 0,
        //                             'section' => $soldItem['section'] ?? 0,
        //                             'cost' => $soldItem['cost'] ?? 0,
        //                             'total' => $soldItem['total'] ?? 0,
        //                             'profit' => $soldItem['profit'] ?? 0,
        //                             'roi' => $soldItem['roi'] ?? 0,
        //                             'invoiceDate' => isset($soldItem['invoiceDate']) 
        //                                 ? Carbon::parse($soldItem['invoiceDate'])->format('Y-m-d H:i:s') 
        //                                 : now(),
        //                             'updated_at' => now(),
        //                         ];
        //                     }
        //                 } catch (Exception $e) {
        //                     Log::error("Error processing Event ID {$inventory->event_id}: " . $e->getMessage());
        //                 }
        //             }
        //         });

        //     if (!empty($soldTicketsData)) {
        //         SoldTicket::upsert($soldTicketsData, ['invoiceId'], [
        //             'event_id', 'customerDisplayName', 'lowSeat','highSeat', 'section', 'cost', 'total', 'profit', 'roi', 'invoiceDate', 'updated_at'
        //         ]);
        //     }

        //     Log::info('FetchSoldTicketsJob completed successfully.');
        // } catch (Exception $e) {
        //     Log::error("FetchSoldTicketsJob encountered an error: " . $e->getMessage());
        // }
    }
}
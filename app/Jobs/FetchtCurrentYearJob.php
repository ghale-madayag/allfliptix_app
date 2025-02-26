<?php

namespace App\Jobs;

use App\Models\Inventory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FetchtCurrentYearJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
            // Prevent multiple instances from running simultaneously
        if (Cache::has('fetch_inventory_running')) {
            Log::info('FetchInventoryJob is already running. Skipping execution.');
            return;
        }

        Cache::put('fetch_inventory_running', true, now()->addMinutes(30));

        try {
            $apiToken = env('SKYBOX_API_TOKEN');
            $authToken = env('SKYBOX_AUTH_TOKEN');

            $inventoryUrl = "https://skybox.vividseats.com/services/inventory?eventDateFrom=2025-01-01T00:00:00";
            //$inventoryUrl = "https://skybox.vividseats.com/services/inventory/";
            $response = Http::withHeaders([
                'X-Api-Token' => $authToken, 
                'X-Application-Token' => $apiToken,
                'Accept' => 'application/json',
            ])->get($inventoryUrl);

            if ($response->failed()) {
                Log::error('Failed to fetch inventory from Skybox API.');
                return;
            }

            $data = $response->json();

            collect($data['rows'])->chunk(500)->each(function ($chunk) use ($authToken, $apiToken) {
                $inventoryData = $chunk->map(function ($item) use ($authToken, $apiToken) {
                    $eventId = $item['event']['id'];
                    $soldTicketsUrl = "https://skybox.vividseats.com/services/inventory/sold?eventId={$eventId}";

                    $soldResponse = Http::withHeaders([
                        'X-Api-Token' => $authToken, 
                        'X-Application-Token' => $apiToken,
                        'Accept' => 'application/json',
                    ])->get($soldTicketsUrl);

                    $soldData = $soldResponse->successful() 
                        ? $soldResponse->json()['soldInventoryTotals']['totalQuantity'] ?? 0 
                        : 0;

                    $totalProfitMargin = $soldResponse->successful() 
                        ? $soldResponse->json()['soldInventoryTotals']['totalProfitMargin'] ?? 0 
                        : 0;

                    return [
                        'event_id' => $eventId,
                        'name' => $item['event']['name'],
                        'date' => $item['event']['date'],
                        'venue' => $item['event']['venue']['name'] ?? 'N/A',
                        'sold' => $soldData,
                        'qty' => $item['quantity'],
                        'profit_margin' => $totalProfitMargin,
                        'stubhub_url' => $item['event']['stubhubEventUrl'] ?? 'N/A',
                        'vivid_url' => $item['event']['vividSeatsEventUrl'] ?? 'N/A',
                        'updated_at' => now()
                    ];
                })->toArray();

                // **Use upsert to update or insert records**
                Inventory::upsert($inventoryData, ['event_id'], [
                    'name', 'date', 'venue', 'sold', 'qty', 'profit_margin', 'stubhub_url', 'vivid_url'
                ]);
            });

            Log::info('FetchCurrentYearJob completed successfully.');
        } catch (\Exception $e) {
            Log::error('FetchCurrentYearJob failed: ' . $e->getMessage());
        } finally {
            Cache::forget('fetch_inventory_running'); // Unlock job execution
        }
    }
}

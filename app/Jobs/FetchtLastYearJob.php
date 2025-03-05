<?php

namespace App\Jobs;

use App\Models\Inventory;
use App\Models\SoldTicket;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FetchtLastYearJob implements ShouldQueue
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
            $lastYear = now()->subYear()->year;
    
            $inventoryUrl = "https://skybox.vividseats.com/services/inventory?eventDateFrom={$lastYear}-01-01T00:00:00";
            $response = Http::withHeaders([
                'X-Api-Token' => $authToken, 
                'X-Application-Token' => $apiToken,
                'Accept' => 'application/json',
            ])->get($inventoryUrl);
    
            if ($response->failed()) {
                Log::error($apiToken);
                return;
            }
    
            $data = $response->json();
            $eventIdsFromAPI = collect($data['rows'])->pluck('event.id')->toArray();
    
            collect($data['rows'])->chunk(100)->each(function ($chunk) use ($authToken, $apiToken) {
                $inventoryData = [];
                $soldTicketsData = [];
    
                foreach ($chunk as $item) {
                    $eventId = $item['event']['id'];
                    $soldTicketsUrl = "https://skybox.vividseats.com/services/inventory/sold?eventId={$eventId}";
    
                    $soldResponse = Http::retry(3, 5000)->withHeaders([
                        'X-Api-Token' => $authToken, 
                        'X-Application-Token' => $apiToken,
                        'Accept' => 'application/json',
                    ])->timeout(60)->get($soldTicketsUrl);
    
                    $soldData = $soldResponse->successful() ? $soldResponse->json() : [];
                    $soldQuantity = $soldData['soldInventoryTotals']['totalQuantity'] ?? 0;
                    $totalProfitMargin = $soldData['soldInventoryTotals']['totalProfitMargin'] ?? 0;
    
                    // Prepare inventory data
                    $inventoryData[] = [
                        'event_id' => $eventId,
                        'name' => $item['event']['name'],
                        'date' => $item['event']['date'],
                        'venue' => $item['event']['venue']['name'] ?? 'N/A',
                        'sold' => $soldQuantity,
                        'qty' => $item['quantity'],
                        'profit_margin' => $totalProfitMargin,
                        'stubhub_url' => $item['event']['stubhubEventUrl'] ?? 'N/A',
                        'vivid_url' => $item['event']['vividSeatsEventUrl'] ?? 'N/A',
                        'updated_at' => now()
                    ];
    
                    // Prepare sold tickets data
                    foreach ($soldData['rows'] ?? [] as $soldItem) {
                        $soldTicketsData[] = [
                            'event_id' => $eventId,
                            'invoiceId' => $soldItem['invoiceId'] ?? 0,
                            'cost' => $soldItem['cost'] ?? 0,
                            'total' => $soldItem['total'] ?? 0,
                            'profit' => $soldItem['profit'] ?? 0,
                            'roi' => $soldItem['roi'] ?? 0,
                            'invoiceDate' => isset($soldItem['invoiceDate']) 
                                ? Carbon::parse($soldItem['invoiceDate'])->format('Y-m-d H:i:s') 
                                : now(),
                            'updated_at' => now(),
                        ];
                    }
                }
    
                // Bulk upsert for inventories
                Inventory::upsert($inventoryData, ['event_id'], [
                    'name', 'date', 'venue', 'sold', 'qty', 'profit_margin', 'stubhub_url', 'vivid_url', 'updated_at'
                ]);
    
                // Bulk upsert for sold tickets
                if (!empty($soldTicketsData)) {
                    SoldTicket::upsert($soldTicketsData, ['invoiceId'], [
                        'event_id', 'cost', 'total', 'profit', 'roi', 'invoiceDate', 'updated_at'
                    ]);
                }
            });
    
            // Update qty to 0 for missing inventory records
            Inventory::whereNotIn('event_id', $eventIdsFromAPI)->update(['qty' => 0]);
    
            Log::info('FetchCurrentYearJob completed successfully.');
        } catch (\Exception $e) {
            Log::error('FetchCurrentYearJob failed: ' . $e->getMessage());
        } finally {
            Cache::forget('fetch_inventory_running'); // Unlock job execution
        }
    }
}

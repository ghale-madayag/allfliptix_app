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

class FetchtCurrentYearJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        if (Cache::has('fetch_inventory_running')) {
            Log::info('FetchInventoryJob is already running. Skipping execution.');
            return;
        }

        Cache::put('fetch_inventory_running', true, now()->addMinutes(30));

        try {
            $apiToken = env('SKYBOX_API_TOKEN');
            $authToken = env('SKYBOX_AUTH_TOKEN');
            $currentYear = now()->year;

            $inventoryUrl = "https://skybox.vividseats.com/services/inventory?eventDateFrom={$currentYear}-01-01T00:00:00";
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
            $eventQuantities = [];

            // STEP 1: Process and save inventory data
            $inventoryData = [];
            foreach ($data['rows'] as $item) {
                $eventId = $item['event']['id'];

                if (!isset($eventQuantities[$eventId])) {
                    $eventQuantities[$eventId] = 0;
                }
                $eventQuantities[$eventId] += $item['quantity'];

                $inventoryData[$eventId] = [
                    'event_id' => $eventId,
                    'name' => $item['event']['name'],
                    'date' => $item['event']['date'],
                    'venue' => $item['event']['venue']['name'] ?? 'N/A',
                    'qty' => $eventQuantities[$eventId], // Summed quantity
                    'profit_margin' => 0, // Placeholder
                    'sold' => 0, // Placeholder, will update later
                    'stubhub_url' => $item['event']['stubhubEventUrl'] ?? 'N/A',
                    'vivid_url' => $item['event']['vividSeatsEventUrl'] ?? 'N/A',
                    'updated_at' => now()
                ];
            }

            Inventory::upsert($inventoryData, ['event_id'], [
                'name', 'date', 'venue', 'qty', 'profit_margin', 'sold', 'stubhub_url', 'vivid_url', 'updated_at'
            ]);

            // STEP 2: Query saved inventory and fetch sold ticket data
            $inventoryRecords = Inventory::whereIn('event_id', array_keys($inventoryData))->get();
            $soldTicketsData = [];

            foreach ($inventoryRecords as $inventory) {
                $eventId = $inventory->event_id;
                $soldTicketsUrl = "https://skybox.vividseats.com/services/inventory/sold?eventId={$eventId}";

                $soldResponse = Http::withHeaders([
                    'X-Api-Token' => $authToken, 
                    'X-Application-Token' => $apiToken,
                    'Accept' => 'application/json',
                ])->get($soldTicketsUrl);

                $soldData = $soldResponse->successful() ? $soldResponse->json() : [];
                $soldQuantity = $soldData['soldInventoryTotals']['totalQuantity'] ?? 0;
                $totalProfitMargin = $soldData['soldInventoryTotals']['totalProfitMargin'] ?? 0;

                // Update inventory record with sold quantity & profit margin
                Inventory::where('event_id', $eventId)->update([
                    'sold' => $soldQuantity,
                    'profit_margin' => $totalProfitMargin,
                    'updated_at' => now()
                ]);

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

            // STEP 3: Bulk upsert for sold tickets
            if (!empty($soldTicketsData)) {
                SoldTicket::upsert($soldTicketsData, ['invoiceId'], [
                    'event_id', 'cost', 'total', 'profit', 'roi', 'invoiceDate', 'updated_at'
                ]);
            }

            // STEP 4: Update qty to 0 for missing inventory records
            Inventory::whereNotIn('event_id', $eventIdsFromAPI)->update(['qty' => 0]);

            // STEP 5: Delete inventory records where qty = 0 and sold = 0
            Inventory::where('qty', 0)->where('sold', 0)->delete();

            Log::info('FetchCurrentYearJob completed successfully.');
        } catch (\Exception $e) {
            Log::error('FetchCurrentYearJob failed: ' . $e->getMessage());
        } finally {
            Cache::forget('fetch_inventory_running');
        }
    }

    
}

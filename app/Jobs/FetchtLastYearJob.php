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
        if (Cache::has('fetch_inventory_running')) {
            Log::info('FetchInventoryJob is already running. Skipping execution.');
            return;
        }

        Cache::put('fetch_inventory_running', true, now()->addMinutes(30));

        try {
            $apiToken = env('SKYBOX_API_TOKEN');
            $authToken = env('SKYBOX_AUTH_TOKEN');
            $lastYear = now()->subYear()->year;

            // Fetch Inventory First
            $inventoryUrl = "https://skybox.vividseats.com/services/inventory?eventDateFrom={$lastYear}-01-01T00:00:00";
            $response = Http::withHeaders([
                'X-Api-Token' => $authToken, 
                'X-Application-Token' => $apiToken,
                'Accept' => 'application/json',
            ])->get($inventoryUrl);

            if ($response->failed()) {
                Log::error("Failed to fetch inventory.");
                return;
            }

            $data = $response->json();
            $eventIdsFromAPI = collect($data['rows'])->pluck('event.id')->toArray();

            $inventoryData = [];

            foreach ($data['rows'] as $item) {
                $inventoryData[] = [
                    'event_id' => $item['event']['id'],
                    'name' => $item['event']['name'],
                    'date' => $item['event']['date'],
                    'venue' => $item['event']['venue']['name'] ?? 'N/A',
                    'qty' => $item['quantity'],
                    'sold' => 0, // Sold will be updated later
                    'profit_margin' => 0, // Will be updated after fetching sold data
                    'stubhub_url' => $item['event']['stubhubEventUrl'] ?? 'N/A',
                    'vivid_url' => $item['event']['vividSeatsEventUrl'] ?? 'N/A',
                    'updated_at' => now()
                ];
            }

            // Save Inventory Data First
            Inventory::upsert($inventoryData, ['event_id'], [
                'name', 'date', 'venue', 'qty', 'sold', 'profit_margin', 'stubhub_url', 'vivid_url', 'updated_at'
            ]);

            Log::info("Inventory data saved successfully.");

            // Fetch Sold Tickets Separately
            collect($eventIdsFromAPI)->chunk(50)->each(function ($chunk) use ($authToken, $apiToken) {
                $soldTicketsData = [];

                foreach ($chunk as $eventId) {
                    $soldTicketsUrl = "https://skybox.vividseats.com/services/inventory/sold?eventId={$eventId}";

                    $soldResponse = Http::withHeaders([
                        'X-Api-Token' => $authToken, 
                        'X-Application-Token' => $apiToken,
                        'Accept' => 'application/json',
                    ])->get($soldTicketsUrl);

                    if ($soldResponse->successful()) {
                        $soldData = $soldResponse->json();
                        $soldQuantity = $soldData['soldInventoryTotals']['totalQuantity'] ?? 0;
                        $totalProfitMargin = $soldData['soldInventoryTotals']['totalProfitMargin'] ?? 0;

                        // Update Inventory with Sold Data
                        Inventory::where('event_id', $eventId)
                            ->update(['sold' => $soldQuantity, 'profit_margin' => $totalProfitMargin]);

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
                }

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

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

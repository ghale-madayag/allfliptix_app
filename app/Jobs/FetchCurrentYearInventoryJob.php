<?php

namespace App\Jobs;

use App\Models\Inventory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchCurrentYearInventoryJob implements ShouldQueue
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
    if (Cache::has('fetch_current_inventory_running')) {
        Log::info('FetchCurrentYearInventoryJob is already running. Skipping execution.');
        return;
    }

    Cache::put('fetch_current_inventory_running', true, now()->addMinutes(10));

    try {
        $apiToken = env('SKYBOX_API_TOKEN');
        $authToken = env('SKYBOX_AUTH_TOKEN');

        $currentYear = now()->year;
        $inventoryUrl = "https://skybox.vividseats.com/services/inventory?eventDateFrom=2022-01-01T00:00:00";

        $response = Http::withHeaders([
            'X-Api-Token' => $authToken, 
            'X-Application-Token' => $apiToken,
            'Accept' => 'application/json',
        ])->get($inventoryUrl);

        if ($response->failed()) {
            Log::error('Failed to fetch current year inventory from Skybox API.');
            return;
        }

        $this->processData($response->json());

        Log::info('FetchCurrentYearInventoryJob completed successfully.');
    } catch (\Exception $e) {
        Log::error('FetchCurrentYearInventoryJob failed: ' . $e->getMessage());
    } finally {
        Cache::forget('fetch_current_inventory_running');
    }
}

    private function processData($data)
    {
        collect($data['rows'])->chunk(500)->each(function ($chunk) {
            $inventoryData = $chunk->map(function ($item) {
                return [
                    'event_id' => $item['event']['id'],
                    'name' => $item['event']['name'],
                    'date' => $item['event']['date'],
                    'venue' => $item['event']['venue']['name'] ?? 'N/A',
                    'qty' => $item['quantity'],
                    'updated_at' => now()
                ];
            })->toArray();

            Inventory::upsert($inventoryData, ['event_id'], ['name', 'date', 'venue', 'qty']);
        });
    }

}

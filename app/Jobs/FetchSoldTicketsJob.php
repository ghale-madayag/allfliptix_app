<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
            $apiToken = env('SKYBOX_API_TOKEN');
            $authToken = env('SKYBOX_AUTH_TOKEN');
            $soldTicketsData = [];

            $startDate = Carbon::now()->subDay()->format('Y-m-d');
            $endDate = Carbon::now()->format('Y-m-d');
            $soldTicketsUrl = "https://skybox.vividseats.com/services/inventory/sold?invoiceDateFrom={$startDate}&invoiceDateTo={$endDate}";

            $soldResponse = Http::withHeaders([
                'X-Api-Token' => $authToken, 
                'X-Application-Token' => $apiToken,
                'Accept' => 'application/json',
            ])->timeout(60)->get($soldTicketsUrl);

            if ($soldResponse->failed()) {
                Log::warning("FetchSoldTicketsJob failed for date range: {$startDate} - {$endDate}");
                return;
            }

            $soldData = $soldResponse->json();
            foreach ($soldData['rows'] ?? [] as $soldItem) {
                $soldTicketsData[] = [
                    'event_id' => $soldItem['eventId'] ?? 0,
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

            if (!empty($soldTicketsData)) {
                SoldTicket::upsert($soldTicketsData, ['invoiceId'], [
                    'event_id', 'cost', 'total', 'profit', 'roi', 'invoiceDate', 'updated_at'
                ]);
            }

            Log::info('FetchSoldTicketsJob completed successfully.');
        } catch (Exception $e) {
            Log::error("FetchSoldTicketsJob encountered an error: " . $e->getMessage());
        }
    }
}

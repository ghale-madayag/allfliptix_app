<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\SoldInventoryTotal;
use App\Models\SoldTicket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SkyboxController extends Controller
{
    public function fetchInventory(Request $request)
    {

        // Run the job synchronously
        try {
            \App\Jobs\FetchSoldTicketsJob::dispatchSync();
        } catch (\Exception $e) {
            Log::error("FetchSoldTicketsJob encountered an error: " . $e->getMessage());
            // Handle the error as needed
        }

        $inventory = Inventory::all()->map(function ($item) {
            return [
                'event_id' => $item->event_id,
                'name' => $item->name,
                'date' => $item->date, // Keep this if you still need it
                'venue' => $item->venue ?? 'N/A',
                'sold' => $item->sold,
                'qty' => $item->qty,
                'profit_margin' => $item->profit_margin,
                'stubhub_url' => $item->stubhub_url ?? 'N/A',
                'vivid_url' => $item->vivid_url ?? 'N/A',
                'updated_at' => $item->updated_at,
                'avg_sold_1d' => round($item->avg_profit_1d, 2),
                'avg_sold_3d' => round($item->avg_profit_3d, 2),
                'avg_sold_7d' => round($item->avg_profit_7d, 2),
                'avg_sold_30d' => round($item->avg_profit_30d, 2),
            ];
        })->toArray();

       // Calculate current and previous month totals
        $thisMonth = SoldInventoryTotal::where('period', 'this_month')->first();
        
        
        return Inertia::render('skybox/index', [
            'inventory' => $inventory,
            'totalQtyThisMonth' => $thisMonth['total_profit'],
            'totalSoldThisMonth' => $thisMonth['total_qty'],
            'totalProfitMarginThisMonth' => $thisMonth['total_profit_margin'],
        ]);  

    }


    // public function fetchInventory(Request $request)
    // {
    //     // Define date ranges
    //     $today = Carbon::today();
    //     $oneDayAgo = $today->copy()->subDay();
    //     $threeDaysAgo = $today->copy()->subDays(3);
    //     $sevenDaysAgo = $today->copy()->subDays(7);
    //     $thirtyDaysAgo = $today->copy()->subDays(30);
    //     $startYear = $today->copy()->startOfYear();

    //     $apiToken = env('SKYBOX_API_TOKEN');
    //     $authToken = env('SKYBOX_AUTH_TOKEN');

    //     // Fetch data from the external API
    //     $url = 'https://skybox.vividseats.com/services/inventory/sold?invoiceDateFrom=' . $startYear->toDateString();
    //     $response = Http::withHeaders([
    //         'X-Api-Token' => $authToken, 
    //         'X-Application-Token' => $apiToken,
    //         'Accept' => 'application/json',
    //     ])->get($url);

    //     if ($response->failed()) {
    //         // Handle error
    //         return response()->json(['error' => 'Failed to fetch data from API'], 500);
    //     }

    //     $data = $response->json();

    //     // Process and aggregate the data
    //     $events = [];

    //     foreach ($data['rows'] as $item) {
    //         $eventId = $item['eventId'];
    //         $quantity = $item['quantity'];
    //         $profit = $item['profitMargin'];
    //         $invoiceDate = Carbon::parse($item['invoiceDate']);

    //         if (!isset($events[$eventId])) {
    //             $events[$eventId] = [
    //                 'event_id' => $eventId,
    //                 'name' => $item['event']['name'],
    //                 'date' => $item['event']['date'],
    //                 'venue' => $item['event']['venue']['name'] ?? 'N/A',
    //                 'sold' => 0,
    //                 'profit_margin' => 0,
    //                 'profit' => [
    //                     '1d' => [],
    //                     '3d' => [],
    //                     '7d' => [],
    //                     '30d' => [],
    //                 ],
    //             ];
    //         }

    //         $events[$eventId]['sold'] += $quantity;
    //         $events[$eventId]['profit_margin'] += $profit;

    //         // Categorize profits based on date ranges
    //         if ($invoiceDate->between($oneDayAgo, $today)) {
    //             $events[$eventId]['profit']['1d'][] = $profit;
    //         }
    //         if ($invoiceDate->between($threeDaysAgo, $today)) {
    //             $events[$eventId]['profit']['3d'][] = $profit;
    //         }
    //         if ($invoiceDate->between($sevenDaysAgo, $today)) {
    //             $events[$eventId]['profit']['7d'][] = $profit;
    //         }
    //         if ($invoiceDate->between($thirtyDaysAgo, $today)) {
    //             $events[$eventId]['profit']['30d'][] = $profit;
    //         }
    //     }

    //     // Calculate average profits
    //     foreach ($events as &$event) {
    //         foreach ($event['profit'] as $key => $profits) {
    //             $event['avg_sold_' . $key] = !empty($profits) ? round(array_sum($profits) / count($profits), 2) : 0;
    //         }
    //         unset($event['profit']); // Remove intermediate profits array
    //     }

    //     // Convert associative array to indexed array
    //     $inventory = array_values($events);

    //     // Calculate summary statistics for the current month
    //     $totalQtyThisMonth = array_sum(array_column($inventory, 'sold'));
    //     $totalProfitThisMonth = array_sum(array_column($inventory, 'profit_margin'));
    //     $totalProfitMarginThisMonth = $totalQtyThisMonth > 0 ? $totalProfitThisMonth / $totalQtyThisMonth : 0;

    //     //dd($inventory);
    //     // Return the processed data
    //     return Inertia::render('skybox/index', [
    //         'inventory' => $inventory,
    //         'totalQtyThisMonth' => $totalQtyThisMonth,
    //         'totalProfitThisMonth' => $totalProfitThisMonth,
    //         'totalProfitMarginThisMonth' => $totalProfitMarginThisMonth,
    //     ]);
    // }



}

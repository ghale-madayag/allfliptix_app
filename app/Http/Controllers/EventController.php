<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class EventController extends Controller
{
    function event(Request $request)
    {
        $eventId = $request->event;

        if (!$eventId) {
            return response()->json(['error' => 'Event ID is required.'], 400);
        }

        $response = Http::withHeaders([
            'X-Api-Token' => env('SKYBOX_AUTH_TOKEN'), 
            'X-Application-Token' => env('SKYBOX_API_TOKEN'),
            'Accept' => 'application/json',
        ])->get("https://skybox.vividseats.com/services/inventory/sold", [
            'eventId' => $eventId
        ]);

        if ($response->failed()) {
            return Inertia::render('skybox/sold_inventory', [
                'sold_inventory' => [],
                'error' => 'Failed to fetch sold inventory data.'
            ]);
        }

        $soldData = $response->json();

        // Extract event details from the first row (if available)
        $eventDetails = null;
        $inventoryDetails = null;

        if (!empty($soldData)) {
            
            $inventoryDetails = [
                'qty' => $soldData['soldInventoryTotals']['totalQuantity'],
                'total' => $soldData['soldInventoryTotals']['totalAmount'],
                'total_profit' => $soldData['soldInventoryTotals']['totalProfit'],
                'profit' => $soldData['soldInventoryTotals']['totalProfitMargin'],
                'roi' => $soldData['soldInventoryTotals']['totalROI'],
            ];
        }

        if (!empty($soldData['rows'][0]['event'])) {
            $event = $soldData['rows'][0]['event'];
            $eventDate = isset($event['date']) ? Carbon::parse($event['date'])->format('M d, Y h:i A') : 'N/A';

            $eventDetails = [
                'name'    => $event['name'] ?? 'N/A',
                'date'    => $eventDate,
                'venue'    => $event['venue']['name'] ?? 'N/A',
                'city'    => $event['venue']['city'] ?? 'N/A',
                'state'   => $event['venue']['state'] ?? 'N/A',
                'country' => $event['venue']['country'] ?? 'N/A',
            ];
        }

        return Inertia::render('skybox/sold_inventory', [
            'sold_inventory' => $soldData,
            'event_details'  => $eventDetails,
            'inventory' => $inventoryDetails,
        ]);
    }

}

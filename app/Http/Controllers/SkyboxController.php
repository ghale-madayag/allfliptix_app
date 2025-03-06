<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\SoldInventoryTotal;
use App\Models\SoldTicket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SkyboxController extends Controller
{
    public function fetchInventory(Request $request)
    {
        // Define date ranges
        $today = Carbon::today();
        $oneDayAgo = Carbon::yesterday();
        $threeDaysAgo = Carbon::now()->subDays(3);
        $sevenDaysAgo = Carbon::now()->subDays(7);
        $thirtyDaysAgo = Carbon::now()->subDays(30);


        $inventory = Inventory::all()->map(function ($item) use ($oneDayAgo, $threeDaysAgo, $sevenDaysAgo, $today, $thirtyDaysAgo) {
            $avgSold1Day = SoldTicket::where('event_id', $item->event_id)
                ->whereBetween('invoiceDate', [$oneDayAgo, $today])
                ->avg('profit') ?? 0;
        
            $avgSold3Days = SoldTicket::where('event_id', $item->event_id)
                ->whereBetween('invoiceDate', [$threeDaysAgo, $today])
                ->avg('profit') ?? 0;
        
            $avgSold7Days = SoldTicket::where('event_id', $item->event_id)
                ->whereBetween('invoiceDate', [$sevenDaysAgo, $today])
                ->avg('profit') ?? 0;

            $avgSold30Days = SoldTicket::where('event_id', $item->event_id)
                ->whereBetween('invoiceDate', [$thirtyDaysAgo, $today])
                ->avg('profit') ?? 0;
        
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
                'avg_sold_1d' => round($avgSold1Day, 2),
                'avg_sold_3d' => round($avgSold3Days, 2),
                'avg_sold_7d' => round($avgSold7Days, 2),
                'avg_sold_30d' => round($avgSold30Days, 2),
            ];
        })->toArray();

        //dd($inventory);

       // Calculate current and previous month totals
        $thisMonth = SoldInventoryTotal::where('period', 'this_month')->first();
        
        
        return Inertia::render('skybox/index', [
            'inventory' => $inventory,
            'totalQtyThisMonth' => $thisMonth['total_profit'],
            'totalSoldThisMonth' => $thisMonth['total_qty'],
            'totalProfitMarginThisMonth' => $thisMonth['total_profit_margin'],
        ]);  

    }


}

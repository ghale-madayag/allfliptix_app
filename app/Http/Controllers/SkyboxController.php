<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\SoldTicket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SkyboxController extends Controller
{
    public function fetchInventory(Request $request)
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $previousMonth = Carbon::now()->subMonth()->startOfMonth();

        // Define date ranges
        $today = Carbon::today();
        $oneDayAgo = Carbon::yesterday();
        $threeDaysAgo = Carbon::now()->subDays(3);
        $sevenDaysAgo = Carbon::now()->subDays(7);

        $inventory = Inventory::all()->map(function ($item) use ($oneDayAgo, $threeDaysAgo, $sevenDaysAgo, $today) {
            $avgSold1Day = SoldTicket::where('event_id', $item->event_id)
                ->whereBetween('invoiceDate', [$oneDayAgo, $today])
                ->avg('profit') ?? 0;
        
            $avgSold3Days = SoldTicket::where('event_id', $item->event_id)
                ->whereBetween('invoiceDate', [$threeDaysAgo, $today])
                ->avg('profit') ?? 0;
        
            $avgSold7Days = SoldTicket::where('event_id', $item->event_id)
                ->whereBetween('invoiceDate', [$sevenDaysAgo, $today])
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
            ];
        })->toArray();

        //dd($inventory);

       // Calculate current and previous month totals
        $totalQtyThisMonth = Inventory::whereBetween('date', [$currentMonth, Carbon::now()->endOfMonth()])->sum('qty');
        $totalQtyLastMonth = Inventory::whereBetween('date', [$previousMonth, $currentMonth->subDay()])->sum('qty');

        $totalSoldThisMonth = Inventory::whereBetween('date', [$currentMonth, Carbon::now()->endOfMonth()])->sum('sold');
        $totalSoldLastMonth = Inventory::whereBetween('date', [$previousMonth, $currentMonth->subDay()])->sum('sold');

        // Compute percentage changes
        $percentageQtyChange = $totalQtyLastMonth > 0 
            ? (($totalQtyThisMonth - $totalQtyLastMonth) / $totalQtyLastMonth) * 100 
            : ($totalQtyThisMonth > 0 ? 100 : 0);

        $percentageSoldChange = $totalSoldLastMonth > 0 
            ? (($totalSoldThisMonth - $totalSoldLastMonth) / $totalSoldLastMonth) * 100 
            : ($totalSoldThisMonth > 0 ? 100 : 0);

        $avgProfitMarginThisMonth = Inventory::whereBetween('date', [$currentMonth, Carbon::now()->endOfMonth()])
        ->selectRaw('SUM(profit_margin) / COUNT(event_id) as avg_profit_margin')
        ->value('avg_profit_margin') ?? 0;
        
        $avgProfitMarginLastMonth = Inventory::whereBetween('date', [$previousMonth, $currentMonth->subDay()])
            ->selectRaw('SUM(profit_margin) / COUNT(event_id) as avg_profit_margin')
            ->value('avg_profit_margin') ?? 0;
        
        $percentageProfitMarginChange = $avgProfitMarginLastMonth > 0 
            ? (($avgProfitMarginThisMonth - $avgProfitMarginLastMonth) / $avgProfitMarginLastMonth) * 100 
            : ($avgProfitMarginThisMonth > 0 ? 100 : 0);
        
        //dd($inventory);
        
        return Inertia::render('skybox/index', [
            'inventory' => $inventory,
            'totalQtyThisMonth' => $totalQtyThisMonth,
            'totalSoldThisMonth' => $totalSoldThisMonth,
            'totalProfitMarginThisMonth' => $avgProfitMarginThisMonth,
            'percentageQtyChange' => round($percentageQtyChange, 2),
            'percentageSoldChange' => round($percentageSoldChange, 2),
            'percentageProfitMarginChange' => round($percentageProfitMarginChange, 2),
        ]);  

    }


}

<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\SoldInventoryTotal;
use App\Models\SoldTicket;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DashboardController extends Controller
{
    
    public function index()
    {
        // $currentYear = Carbon::now()->year;
        // $lastYear = Carbon::now()->subYear()->year;
        // $currentMonth = Carbon::now()->month;

        // $profitThisYear = Inventory::selectRaw('MONTH(date) as month, 
        //     SUM(profit_margin) / COUNT(event_id) as total_profit_margin')
        //     ->whereYear('date',  $currentYear)
        //     ->groupBy('month')
        //     ->orderBy('month')
        //     ->pluck('total_profit_margin', 'month');

        // $profitLastYear = Inventory::selectRaw('MONTH(date) as month, 
        //     SUM(profit_margin) / COUNT(event_id) as total_profit_margin')
        //     ->whereYear('date', $lastYear)
        //     ->groupBy('month')
        //     ->orderBy('month')
        //     ->pluck('total_profit_margin', 'month');

        // $dataThisYear = array_fill(0, 12, 0);
        // $dataLastYear = array_fill(0, 12, 0);

        // foreach ($profitThisYear as $month => $value) {
        //     $dataThisYear[$month - 1] = round($value, 2);
        // }

        // foreach ($profitLastYear as $month => $value) {
        //     $dataLastYear[$month - 1] = round($value, 2);
        // }

        // Run the job synchronously
        try {
            \App\Jobs\FetchtCurrentYearJob::dispatchSync();
            \App\Jobs\FetchtLastYearJob::dispatchSync();
            \App\Jobs\FetchProfitJob::dispatchSync();
        } catch (\Exception $e) {
            Log::error("Fetch encountered an error: " . $e->getMessage());
            // Handle the error as needed
        }

        $currentYear = now()->year;
        $lastYear = now()->year - 1;

        // Retrieve data for this year and last year
        $profitThisYear = SoldInventoryTotal::where('period', 'like', "$currentYear-%")
        ->orderBy('period')
        ->pluck('total_profit_margin', 'period');

        $profitLastYear = SoldInventoryTotal::where('period', 'like', "$lastYear-%")
        ->orderBy('period')
        ->pluck('total_profit_margin', 'period');

        // Initialize empty arrays for 12 months
        $formattedProfits = [
        'profitThisYear' => array_fill(0, 12, 0),
        'profitLastYear' => array_fill(0, 12, 0),
        ];

        // Fill in profits based on period (YYYY-MM format)
        foreach ($profitThisYear as $period => $profit) {
            $monthIndex = intval(substr($period, 5, 2)) - 1; // Extract month from "YYYY-MM"
            $formattedProfits['profitThisYear'][$monthIndex] = $profit;
        }

        foreach ($profitLastYear as $period => $profit) {
            $monthIndex = intval(substr($period, 5, 2)) - 1; // Extract month from "YYYY-MM"
            $formattedProfits['profitLastYear'][$monthIndex] = $profit;
        }

        $currentMonth = now()->format('Y-m'); // Format: YYYY-MM (e.g., 2025-03)
        $lastMonth = now()->subMonth()->format('Y-m'); // Last month in YYYY-MM format
        
        // Get total quantity for the current month
        $currentMonthQty = SoldInventoryTotal::where('period', $currentMonth)
            ->sum('total_qty');
        
        // Get total quantity for the last month
        $lastMonthQty = SoldInventoryTotal::where('period', $lastMonth)
            ->sum('total_qty');
        
        // Output result
        $monthlyQty = [
            'currentMonthQty' => $currentMonthQty,
            'lastMonthQty' => $lastMonthQty,
        ];


        $sold7Days = SoldInventoryTotal::where('period', '7d')->first();
        $sold30Days = SoldInventoryTotal::where('period', '30d')->first();
        $sold90Days = SoldInventoryTotal::where('period', '90d')->first();
        $sold365DaysTotal = SoldInventoryTotal::where('period', 'like', '365d_part_%')->sum('total_profit');

        $topSoldTickets = Inventory::selectRaw('
            event_id, 
            DATE_FORMAT(date, "%d") as date, 
            DATE_FORMAT(date, "%b") as month, 
            DATE_FORMAT(date, "%h:%i%p") as time,
            name, 
            SUM(sold) as total_sold
        ')
        ->whereYear('date', $currentYear)
        ->groupBy('event_id', 'date', 'month', 'time', 'name')
        ->orderByDesc('total_sold')
        ->limit(5) // Get top 5 sold tickets
        ->get();

        return Inertia::render('dashboards/index', [
            'profitThisYear' => $formattedProfits['profitThisYear'],
            'profitLastYear' => $formattedProfits['profitLastYear'],
            'qtyThisMonth' => [$monthlyQty['currentMonthQty']], // Wrap value in array [444]
            'soldThisMonth' => [$monthlyQty['lastMonthQty']], 
            'profit7Days' => $sold7Days['total_profit'],
            'profit30Days' => $sold30Days['total_profit'],
            'profit90Days' =>  $sold90Days['total_profit'],
            'profit365Days' =>  $sold365DaysTotal,
            'topSoldTickets' => $topSoldTickets
        ]);
    }



    public function fetchSoldTotals($from, $to) {
        // Generate a unique cache key based on the date range
        $cacheKey = "sold_totals_{$from}_{$to}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($from, $to) {
            $response = Http::withHeaders([
                'X-Api-Token' => env('SKYBOX_AUTH_TOKEN'), 
                'X-Application-Token' => env('SKYBOX_API_TOKEN'),
                'Accept' => 'application/json',
            ])->get("https://skybox.vividseats.com/services/inventory/sold", [
                'invoiceDateFrom' => $from,
                'invoiceDateTo' => $to,
            ]);

            if ($response->successful()) {
                return $response->json()['soldInventoryTotals']['totalProfit'] ?? 0;
            }

            return 0; // Default value if API fails
        });
    }


}

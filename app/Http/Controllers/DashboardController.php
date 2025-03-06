<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\SoldInventoryTotal;
use App\Models\SoldTicket;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class DashboardController extends Controller
{
    
    public function index()
    {
        $currentYear = Carbon::now()->year;
        $lastYear = Carbon::now()->subYear()->year;
        $currentMonth = Carbon::now()->month;

        $profitThisYear = Inventory::selectRaw('MONTH(date) as month, 
            SUM(profit_margin) / COUNT(event_id) as total_profit_margin')
            ->whereYear('date',  $currentYear)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total_profit_margin', 'month');

        $profitLastYear = Inventory::selectRaw('MONTH(date) as month, 
            SUM(profit_margin) / COUNT(event_id) as total_profit_margin')
            ->whereYear('date', $lastYear)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total_profit_margin', 'month');

        $dataThisYear = array_fill(0, 12, 0);
        $dataLastYear = array_fill(0, 12, 0);

        foreach ($profitThisYear as $month => $value) {
            $dataThisYear[$month - 1] = round($value, 2);
        }

        foreach ($profitLastYear as $month => $value) {
            $dataLastYear[$month - 1] = round($value, 2);
        }

        // Get total qty and sold this month
        $data = Inventory::selectRaw('
            SUM(qty) as total_qty, 
            SUM(sold) as total_sold
        ')
        ->whereMonth('date', $currentMonth)
        ->first();

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
            'profitThisYear' => $dataThisYear,
            'profitLastYear' => $dataLastYear,
            'qtyThisMonth' => [$data->total_qty ?? 0], 
            'soldThisMonth' => [$data->total_sold ?? 0],
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

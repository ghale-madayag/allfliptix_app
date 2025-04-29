<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\SoldInventoryTotal;
use App\Models\SoldTicket;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DashboardController extends Controller
{
    
    public function index()
{
    try {
        $currentYear = now()->year;
        $lastYear = now()->year - 1;

        $profitThisYear = SoldInventoryTotal::where('period', 'like', "$currentYear-%")
            ->orderBy('period')
            ->pluck('total_profit_margin', 'period');

        $profitLastYear = SoldInventoryTotal::where('period', 'like', "$lastYear-%")
            ->orderBy('period')
            ->pluck('total_profit_margin', 'period');

        $formattedProfits = [
            'profitThisYear' => array_fill(0, 12, 0),
            'profitLastYear' => array_fill(0, 12, 0),
        ];

        foreach ($profitThisYear as $period => $profit) {
            $monthIndex = intval(substr($period, 5, 2)) - 1;
            $formattedProfits['profitThisYear'][$monthIndex] = $profit;
        }

        foreach ($profitLastYear as $period => $profit) {
            $monthIndex = intval(substr($period, 5, 2)) - 1;
            $formattedProfits['profitLastYear'][$monthIndex] = $profit;
        }

        $currentMonth = now()->format('Y-m');
        $lastMonth = now()->subMonth()->format('Y-m');

        $monthlyQty = [
            'currentMonthQty' => SoldInventoryTotal::where('period', $currentMonth)->sum('total_qty'),
            'lastMonthQty' => SoldInventoryTotal::where('period', $lastMonth)->sum('total_qty'),
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
            ->limit(5)
            ->get();

        // SKYBOX API
        $apiToken = env('SKYBOX_API_TOKEN');
        $authToken = env('SKYBOX_AUTH_TOKEN');
        $startYear = Carbon::now()->startOfYear();
        $url = 'https://skybox.vividseats.com/services/inventory/sold?invoiceDateFrom=' . $startYear->toDateString();

        $response = Http::withHeaders([
            'X-Api-Token' => $authToken,
            'X-Application-Token' => $apiToken,
            'Accept' => 'application/json',
        ])->get($url);

        if ($response->failed()) {
            Log::error('Failed to fetch data from API');
            $ticketCounts = [];
        } else {
            $data = $response->json();
            $customerCounts = [];

            foreach ($data['rows'] as $item) {
                $customer = $item['customerDisplayName'] ?? null;
                if ($customer) {
                    $customerCounts[$customer] = ($customerCounts[$customer] ?? 0) + 1;
                }
            }

            $ticketCounts = collect($customerCounts)
                ->map(fn($count, $name) => ['customerDisplayName' => $name, 'count' => $count])
                ->sortByDesc('count')
                ->values()
                ->all();
        }
        return Inertia::render('dashboards/index', [
            'profitThisYear' => $formattedProfits['profitThisYear'],
            'profitLastYear' => $formattedProfits['profitLastYear'],
            'qtyThisMonth' => [$monthlyQty['currentMonthQty']],
            'soldThisMonth' => [$monthlyQty['lastMonthQty']],
            'profit7Days' => $sold7Days['total_profit'] ?? 0,
            'profit30Days' => $sold30Days['total_profit'] ?? 0,
            'profit90Days' => $sold90Days['total_profit'] ?? 0,
            'profit365Days' => $sold365DaysTotal,
            'topSoldTickets' => $topSoldTickets,
            'ticketCounts' => $ticketCounts,
        ]);

    } catch (\Throwable $e) {
        Log::error('Dashboard load failed: ' . $e->getMessage());
        abort(500, 'Dashboard failed to load.');
    }
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

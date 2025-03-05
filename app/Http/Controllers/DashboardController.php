<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\SoldTicket;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    // public function index()
    // {
    //     $currentYear = Carbon::now()->year;
    //     $lastYear = Carbon::now()->subYear()->year;
    //     $currentMonth = Carbon::now()->month;

    //     $profitThisYear = Inventory::selectRaw('MONTH(date) as month, 
    //         SUM(profit_margin) / COUNT(event_id) as total_profit_margin')
    //         ->whereYear('date',  $currentYear)
    //         ->groupBy('month')
    //         ->orderBy('month')
    //         ->pluck('total_profit_margin', 'month');

    //     $profitLastYear = Inventory::selectRaw('MONTH(date) as month, 
    //          SUM(profit_margin) / COUNT(event_id) as total_profit_margin')
    //         ->whereYear('date', $lastYear)
    //         ->groupBy('month')
    //         ->orderBy('month')
    //         ->pluck('total_profit_margin', 'month');

    //     $dataThisYear = array_fill(0, 12, 0);
    //     $dataLastYear = array_fill(0, 12, 0);

    //     $data = Inventory::selectRaw('
    //         SUM(qty) as total_qty, 
    //         SUM(sold) as total_sold
    //     ')
    //     ->whereMonth('date', $currentMonth)
    //     ->first();

    //     foreach ($profitThisYear as $month => $value) {
    //         $dataThisYear[$month - 1] = round($value, 2);
    //     }

    //     foreach ($profitLastYear as $month => $value) {
    //         $dataLastYear[$month - 1] = round($value, 2);
    //     }


    //     $topSoldTickets = Inventory::selectRaw('
    //             event_id, 
    //             DATE_FORMAT(date, "%d") as date, 
    //             DATE_FORMAT(date, "%b") as month, 
    //             DATE_FORMAT(date, "%h:%i%p") as time,
    //             name, 
    //             SUM(sold) as total_sold
    //         ')
    //         ->whereYear('date', $currentYear)
    //         ->groupBy('event_id', 'date', 'month', 'time', 'name')
    //         ->orderByDesc('total_sold')
    //         ->limit(5) // Get top 5 sold tickets
    //         ->get();


    //     return Inertia::render('dashboards/index', [
    //         'profitThisYear' => $dataThisYear,
    //         'profitLastYear' => $dataLastYear,
    //         'qtyThisMonth' => [$data->total_qty ?? 0], 
    //         'soldThisMonth' => [$data->total_sold ?? 0],
    //         'topSoldTickets' => $topSoldTickets
    //     ]);

    // }
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

        // Get profits based on SoldTicket invoiceDate
        $sevenDaysAgo = Carbon::now()->subDays(7);
        $oneMonthAgo = Carbon::now()->subMonth();
        $ninetyDaysAgo = Carbon::now()->subDays(90);
        $oneYearAgo = Carbon::now()->subDays(365);

        $profit7Days = SoldTicket::whereBetween('invoiceDate', [$sevenDaysAgo, Carbon::now()])
            ->sum('profit') ?? 0;

        $profit30Days = SoldTicket::whereBetween('invoiceDate', [$oneMonthAgo, Carbon::now()])
            ->sum('profit') ?? 0;
        

        $profit90Days = SoldTicket::whereBetween('invoiceDate', [$ninetyDaysAgo, Carbon::now()])
            ->sum('profit') ?? 0;

        $profit365Days = SoldTicket::whereBetween('invoiceDate', [$oneYearAgo, Carbon::now()])
            ->sum('profit') ?? 0;

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
            'profit7Days' => round($profit7Days, 2),
            'profit30Days' => round($profit30Days, 2),
            'profit90Days' => round($profit90Days, 2),
            'profit365Days' => round($profit365Days, 2),
            'topSoldTickets' => $topSoldTickets
        ]);
    }

}

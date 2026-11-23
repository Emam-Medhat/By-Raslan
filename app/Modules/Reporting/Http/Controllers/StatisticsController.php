<?php

namespace App\Modules\Reporting\Http\Controllers;

use App\Modules\Accounting\Models\Expense;
use App\Modules\Sales\Models\Sale;
use Illuminate\View\View;

class StatisticsController
{
    public function index(): View
    {
        $rows = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();

            $rows[] = [
                'day' => $date,
                'sales' => round((float) Sale::whereDate('at', $date)->sum('total'), 2),
                'expenses' => round((float) Expense::whereDate('day', $date)->where('status', 'ok')->sum('amount'), 2),
                'invoices' => Sale::whereDate('at', $date)->count(),
            ];
        }

        return view('statistics.index', compact('rows'));
    }
}
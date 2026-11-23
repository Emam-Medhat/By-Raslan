<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Modules\Catalog\Models\Item;
use App\Modules\Inventory\Services\StockService;
use App\Modules\Organization\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController
{
    public function __construct(private readonly StockService $stock)
    {
    }

    public function index(): View
    {
        $balances = $this->stock->balance();
        $items = Item::whereIn('id', $balances->pluck('item_id')->unique())->get()->keyBy('id');
        $locations = Location::all()->keyBy('id');

        return view('stock.index', compact('balances', 'items', 'locations'));
    }

    public function adjust(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'item_id' => ['required', 'exists:items,id'],
            'location_id' => ['required', 'exists:locations,id'],
            'qty' => ['required', 'numeric'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $this->stock->adjust(
            (int) $data['item_id'],
            $data['location_id'],
            (float) $data['qty'],
            $data['note'] ?? null,
            auth()->id(),
        );

        return back()->with('status', 'تم تسجيل التسوية');
    }

    public function disposePage(): View
    {
        $locations = Location::whereIn('type', ['dark', 'shop', 'warehouse'])->orderBy('name')->get();

        return view('stock.dispose', compact('locations'));
    }

    public function export(StockService $stock)
    {
        $filename = 'stock-'.now()->format('Y-m-d').'.csv';
        $balances = $stock->balance();
        $items = Item::whereIn('id', $balances->pluck('item_id')->unique())->get()->keyBy('id');
        $locations = Location::all()->keyBy('id');

        return response()->streamDownload(function () use ($balances, $items, $locations) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['الصنف', 'الموقع', 'الرصيد']);
            foreach ($balances as $b) {
                fputcsv($out, [
                    $items[$b['item_id']]->name ?? $b['item_id'],
                    $locations[$b['location_id']]->name ?? $b['location_id'],
                    $b['qty'],
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function dispose(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'location_id' => ['required', 'exists:locations,id'],
            'kind' => ['required', 'in:waste,donate'],
            'boxes' => ['required', 'string'],
            'reason' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        $permission = $data['kind'] === 'waste' ? 'stock.waste' : 'stock.donate';
        abort_unless($request->user()->hasPermission($permission), 403, 'لا تملك الصلاحية');

        $sns = array_values(array_filter(array_map('intval', preg_split('/[\s,،]+/u', $data['boxes'], -1, PREG_SPLIT_NO_EMPTY) ?: [])));

        try {
            $count = $this->stock->disposeBoxes($data['location_id'], $data['kind'], $sns, $data['reason'], auth()->id());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['boxes' => $e->getMessage()]);
        }

        return back()->with('status', "تم تنفيذ العملية على {$count} علبة");
    }
}
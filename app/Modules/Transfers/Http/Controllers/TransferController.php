<?php

namespace App\Modules\Transfers\Http\Controllers;

use App\Modules\Inventory\Models\Box;
use App\Modules\Inventory\Models\StockMove;
use App\Modules\Organization\Models\Location;
use App\Modules\Transfers\Models\Transfer;
use App\Modules\Transfers\Services\TransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransferController
{
    public function __construct(private readonly TransferService $transfers)
    {
    }

    public function index(): View
    {
        $transfers = Transfer::withCount('boxes')->orderByDesc('id')->get();

        return view('transfers.index', compact('transfers'));
    }

    public function create(): View
    {
        $locations = Location::orderBy('name')->get();

        return view('transfers.create', compact('locations'));
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'from_loc' => ['required', 'exists:locations,id'],
            'to_loc' => ['required', 'exists:locations,id'],
            'boxes' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $transfer = $this->transfers->send(
                $data['from_loc'],
                $data['to_loc'],
                $this->sns($data['boxes']),
                $data['reason'] ?? null,
                $data['note'] ?? null,
                auth()->id(),
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['boxes' => $e->getMessage()]);
        }

        return redirect()->route('transfers.show', $transfer)->with('status', 'تم إنشاء التحويل');
    }

    public function show(Transfer $transfer): View
    {
        $transfer->load('boxes');

        return view('transfers.show', compact('transfer'));
    }

    public function receive(Request $request, Transfer $transfer): RedirectResponse
    {
        $data = $request->validate(['boxes' => ['required', 'string']]);

        try {
            $missing = $this->transfers->receive($transfer->id, $this->sns($data['boxes']), auth()->id());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['boxes' => $e->getMessage()]);
        }

        return back()->with('status', "تم الاستلام — الناقص: {$missing}");
    }

    /** @return list<int> */
    private function sns(string $raw): array
    {
        return array_values(array_filter(array_map('intval', preg_split('/[\s,،]+/u', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [])));
    }
}
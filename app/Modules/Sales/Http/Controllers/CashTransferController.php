<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Models\User;
use App\Modules\Sales\Models\CashTransfer;
use App\Modules\Sales\Models\Shift;
use App\Modules\Sales\Services\ShiftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashTransferController
{
    public function __construct(private readonly ShiftService $shifts)
    {
    }

    public function index(Request $request): View
    {
        $query = CashTransfer::with(['fromShift', 'toShift', 'fromUser', 'toUser', 'approver'])->orderByDesc('id');

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $transfers = $query->paginate(20)->withQueryString();

        return view('cash-transfers.index', compact('transfers'));
    }

    public function create(Shift $shift): View
    {
        $users = User::where('active', true)->orderBy('name')->get(['id', 'name']);

        return view('cash-transfers.create', compact('shift', 'users'));
    }

    public function store(Request $request, Shift $shift): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:to_manager,to_next_shift,to_safe'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'to_user_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->shifts->addTransfer($shift, $validated);

        return redirect()->route('shifts.show', $shift)->with('status', 'تم تسجيل التحويل');
    }

    public function approve(CashTransfer $transfer): RedirectResponse
    {
        $transfer->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
        ]);

        return back()->with('status', 'تم اعتماد التحويل');
    }

    public function reject(CashTransfer $transfer): RedirectResponse
    {
        $transfer->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
        ]);

        return back()->with('status', 'تم رفض التحويل');
    }
}
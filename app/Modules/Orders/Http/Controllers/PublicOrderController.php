<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Modules\Notifications\Models\PushToken;
use App\Modules\Orders\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicOrderController
{
    public function __construct(private readonly OrderService $orders)
    {
    }

    public function shop(): View
    {
        $branches = $this->orders->branches();
        $location = request('location', $branches[0]['id'] ?? null);
        $menu = $location ? $this->orders->menu($location) : [];

        return view('public.order', compact('branches', 'location', 'menu'));
    }

    public function trackPage(string $token): View
    {
        $order = $this->orders->track($token);

        return view('public.track', compact('order', 'token'));
    }

    public function branches(): JsonResponse
    {
        return response()->json($this->orders->branches());
    }

    public function menu(Request $request): JsonResponse
    {
        return response()->json($this->orders->menu((string) $request->query('location', '')));
    }

    public function place(Request $request): JsonResponse
    {
        try {
            return response()->json(['ok' => true, 'order' => $this->orders->placeOrder($request->all())]);
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function track(string $token): JsonResponse
    {
        try {
            $order = $this->orders->track($token);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => 'الطلب غير موجود'], 404);
        }

        return response()->json([
            'no' => $order->no,
            'status' => $order->status,
            'est_total' => $order->est_total,
            'total' => $order->total,
            'delivery_fee' => $order->delivery_fee,
            'cancel_reason' => $order->cancel_reason,
            'lines' => $order->lines->map(fn ($l) => [
                'name' => $l->item?->name,
                'unit' => $l->item?->unit,
                'qty' => $l->qty,
                'prepared' => $l->prepared,
                'price' => $l->unit_price,
            ]),
        ]);
    }

    public function pushToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:20'],
            'customer_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'integer'],
        ]);

        PushToken::updateOrCreate(
            ['token' => $data['token']],
            ['platform' => $data['platform'] ?? null, 'customer_id' => $data['customer_id'] ?? null, 'user_id' => $data['user_id'] ?? null],
        );

        return response()->json(['ok' => true]);
    }

    // Mobile app compatibility (Supabase RPC-style endpoints)
    public function appConfig(): JsonResponse
    {
        $branches = $this->orders->branches();

        return response()->json([
            'brands' => ['rostos' => 'روستوز', 'market' => 'رسلان ماركت', 'oricci' => 'تودزي'],
            'note' => 'السعر النهائي بيتحسب بالوزن الفعلي للعلبة',
            'test_mode' => false,
            'branches' => $branches,
        ]);
    }

    public function appMenu(Request $request): JsonResponse
    {
        $loc = (string) ($request->query('p_loc') ?? $request->query('location') ?? '');

        return response()->json($this->orders->menu($loc));
    }

    public function appPlaceOrder(Request $request): JsonResponse
    {
        try {
            $result = $this->orders->placeOrder($request->all());

            return response()->json([['order_no' => $result['no'], 'token' => $result['token'], 'est_total' => $result['est_total']]]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function appTrack(Request $request): JsonResponse
    {
        $token = (string) ($request->query('p_token') ?? $request->query('token') ?? '');
        try {
            $order = $this->orders->track($token);
        } catch (\Throwable $e) {
            return response()->json(null, 404);
        }

        return response()->json($this->trackShape($order));
    }

    private function trackShape($order): array
    {
        $order->loadMissing(['lines.item', 'location']);
        return [
            'no' => $order->no,
            'status' => $order->status,
            'branch' => $order->location?->name ?? $order->location_id,
            'est_total' => $order->est_total,
            'total' => $order->total,
            'fee' => $order->delivery_fee,
            'cancel_reason' => $order->cancel_reason,
            'lines' => $order->lines->map(fn ($l) => [
                'name' => $l->item?->name,
                'unit' => $l->item?->unit,
                'qty' => $l->qty,
                'prepared' => $l->prepared,
                'price' => $l->unit_price,
            ])->values(),
        ];
    }

    // Supabase RPC compatibility (best-effort; auth differs from Supabase)
    public function rpcAppConfig(): JsonResponse { return $this->appConfig(); }
    public function rpcAppMenu(Request $r): JsonResponse { return response()->json($this->orders->menu((string) $r->input('p_loc', $r->input('location', '')))); }
    public function rpcTrackOrder(Request $r): JsonResponse { $r->query->set('p_token', $r->input('p_token', $r->input('token'))); return $this->appTrack($r); }
    public function rpcRegisterPush(Request $r): JsonResponse { return $this->pushToken($r); }
    public function rpcAppPlaceOrder(Request $r): JsonResponse
    {
        try {
            $payload = $r->all();
            // mobile sends p_address,p_note,p_pay,p_lines; web-style placeOrder expects location_id/name/phone/address/lines
            if (isset($payload['p_address']) && ! isset($payload['location_id'])) {
                $addr = \App\Modules\Orders\Models\CustomerAddress::find($payload['p_address']);
                if (! $addr) return response()->json(['message' => 'اختار العنوان'], 422);
                $customer = $addr->customer;
                $payload = array_merge($payload, [
                    'location_id' => $addr->location_id,
                    'name' => $customer?->name ?? '',
                    'phone' => $customer?->phone ?? '',
                    'address' => $addr->address,
                    'area' => $addr->area,
                    'note' => $payload['p_note'] ?? null,
                    'pay' => $payload['p_pay'] ?? 'cash',
                    'lines' => $payload['p_lines'] ?? [],
                    'channel' => 'web',
                ]);
            }
            $result = $this->orders->placeOrder($payload);
            return response()->json([['order_no' => $result['no'], 'token' => $result['token'], 'est_total' => $result['est_total']]]);
        } catch (\RuntimeException $e) { return response()->json(['message' => $e->getMessage()], 422); }
    }
    public function rpcAppMe(Request $r): JsonResponse
    {
        $u = $r->user();
        if (! $u) return response()->json(['message' => 'سجّل دخول الأول'], 401);
        $c = \App\Modules\Orders\Models\Customer::where('auth_id', $u->id)->orWhere('phone', $u->phone)->first();
        if (! $c) return response()->json(['id' => null, 'name' => $u->name, 'phone' => $u->phone, 'addresses' => []]);
        $addrs = \App\Modules\Orders\Models\CustomerAddress::where('customer_id', $c->id)->where('active', true)->orderByDesc('is_default')->orderBy('id')->get();
        return response()->json(['id' => $c->id, 'name' => $c->name, 'phone' => $c->phone, 'addresses' => $addrs->map(fn($a)=>['id'=>$a->id,'label'=>$a->label,'area'=>$a->area,'address'=>$a->address,'location_id'=>$a->location_id,'is_default'=>$a->is_default])->values()]);
    }
    public function rpcSaveName(Request $r): JsonResponse
    {
        $u = $r->user(); if (! $u) return response()->json(['message' => 'سجّل دخول الأول'], 401);
        $name = trim((string) $r->input('p_name', '')); if ($name === '') return response()->json(['message' => 'اكتب اسمك'], 422);
        $c = \App\Modules\Orders\Models\Customer::firstOrCreate(['phone' => $u->phone ?? $u->id], ['name' => $name, 'auth_id' => $u->id]);
        $c->update(['name' => $name, 'auth_id' => $u->id]);
        return response()->json(null);
    }
    public function rpcSaveAddress(Request $r): JsonResponse
    {
        $u = $r->user(); if (! $u) return response()->json(['message' => 'سجّل دخول الأول'], 401);
        $c = \App\Modules\Orders\Models\Customer::firstOrCreate(['phone' => $u->phone ?? $u->id], ['name' => $u->name ?? '', 'auth_id' => $u->id]);
        $addr = trim((string) $r->input('p_address', '')); if ($addr === '') return response()->json(['message' => 'اكتب العنوان'], 422);
        $def = (bool) $r->input('p_default', false);
        if ($def) \App\Modules\Orders\Models\CustomerAddress::where('customer_id', $c->id)->update(['is_default' => false]);
        $id = $r->input('p_id');
        $data = ['label' => $r->input('p_label', 'البيت') ?: 'البيت', 'area' => $r->input('p_area'), 'address' => $addr, 'location_id' => $r->input('p_loc'), 'is_default' => $def, 'active' => true];
        if ($id) { $a = \App\Modules\Orders\Models\CustomerAddress::where('id', $id)->where('customer_id', $c->id)->firstOrFail(); $a->update($data); return response()->json($a->id); }
        $a = $c->addresses()->create($data); return response()->json($a->id);
    }
    public function rpcRemoveAddress(Request $r): JsonResponse
    {
        $u = $r->user(); if (! $u) return response()->json(['message' => 'سجّل دخول الأول'], 401);
        $c = \App\Modules\Orders\Models\Customer::where('auth_id', $u->id)->orWhere('phone', $u->phone)->first();
        if ($c) \App\Modules\Orders\Models\CustomerAddress::where('id', $r->input('p_id'))->where('customer_id', $c->id)->update(['active' => false, 'is_default' => false]);
        return response()->json(null);
    }
    public function rpcMyOrders(Request $r): JsonResponse
    {
        $u = $r->user(); if (! $u) return response()->json(['message' => 'سجّل دخول الأول'], 401);
        $c = \App\Modules\Orders\Models\Customer::where('auth_id', $u->id)->orWhere('phone', $u->phone)->first();
        if (! $c) return response()->json([]);
        $orders = \App\Modules\Orders\Models\OnlineOrder::with(['lines.item','location'])->where('customer_id', $c->id)->orderByDesc('id')->limit(30)->get();
        return response()->json($orders->map(fn($o)=>[
            'no'=>$o->no,'token'=>$o->token,'status'=>$o->status,'created_at'=>$o->created_at,
            'branch'=>$o->location?->name, 'location_id'=>$o->location_id, 'total'=>$o->total ?? ((float)$o->est_total + (float)$o->delivery_fee),
            'lines'=>$o->lines->map(fn($l)=>['item_id'=>$l->item_id,'name'=>$l->item?->name,'qty'=>$l->qty,'unit'=>$l->item?->unit])->values(),
        ])->values());
    }
    public function rpcRequestDelete(Request $r): JsonResponse
    {
        $u = $r->user(); if (! $u) return response()->json(['message' => 'سجّل دخول الأول'], 401);
        // Best-effort: flag request; real deletion flow should be reviewed.
        \Illuminate\Support\Facades\Log::info('app_request_delete', ['user_id' => $u->id]);
        return response()->json(null);
    }
    public function rpcTestLogin(Request $r): JsonResponse
    {
        // Supabase-specific test login; not supported in Laravel auth.
        return response()->json(['message' => 'Not supported in Laravel build'], 501);
    }
}

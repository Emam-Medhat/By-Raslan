<?php

namespace App\Modules\Settings\Http\Controllers;

use App\Modules\Organization\Models\Location;
use App\Modules\Settings\Models\Setting;
use App\Modules\Settings\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController
{
    public function __construct(private readonly SettingsService $settings)
    {
    }

    public function index(): View
    {
        $all = Setting::pluck('value', 'key')->all();
        $locs = Location::whereIn('type', ['dark', 'shop'])->orderBy('sort')->get();
        $fees = json_decode((string) ($all['online.delivery_fee'] ?? '{}'), true) ?: [];

        return view('settings.index', compact('all', 'locs', 'fees'));
    }

    public function update(Request $request): RedirectResponse
    {
        $fees = [];
        foreach ($request->all() as $k => $v) {
            if (str_starts_with((string) $k, 'fee:') && trim((string) $v) !== '') {
                $fees[substr((string) $k, 4)] = (float) $v;
            }
        }

        $this->settings->set('online.open', $request->boolean('open'));
        $this->settings->set('online.min_order', (float) $request->input('min', 0));
        $this->settings->set('online.late_minutes', (int) $request->input('late', 45));
        $this->settings->set('online.delivery_fee', $fees);
        $this->settings->set('online.weight_note', (string) $request->input('note', ''));
        $this->settings->set('app.test_mode', $request->boolean('test'));

        return back()->with('status', 'تم حفظ الإعدادات');
    }
}
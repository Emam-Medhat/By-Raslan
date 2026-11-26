<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Models\Box;

class BoxLabelService
{
    public function stickerHtml(Box $box): string
    {
        $box->loadMissing('item');
        $item = $box->item;
        $uid = e($box->uid ?? $box->sn);
        $name = e($item?->name ?? '');
        $code = e($item?->scale_plu ?? $item?->id ?? '');
        $weight = number_format((float) $box->weight, 3);
        $prod = e(optional($box->produced_at ?? $box->packed_at)->format('Y-m-d H:i'));
        $days = (int) ($item?->shelf_life_days ?? 0);
        $expiry = $box->expires_at ? e($box->expires_at->format('Y-m-d')) : '—';
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' . urlencode((string) ($box->uid ?? $box->sn));

        return <<<HTML
<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><title>Sticker {$uid}</title>
<style>
body{font-family:Tahoma,Arial,sans-serif;margin:0;padding:12px;background:#f5f5f5}
.sticker{width:320px;background:#fff;border:1px solid #ddd;border-radius:12px;padding:14px;margin:0 auto}
.row{display:flex;justify-content:space-between;gap:8px;margin:6px 0;font-size:13px}
.name{font-size:16px;font-weight:700;margin-bottom:8px}
.uid{font-size:18px;font-weight:800;letter-spacing:1px}
.qr{text-align:center;margin-top:10px}
.meta{font-size:12px;color:#555}
@media print{body{background:#fff;padding:0}.sticker{border:1px dashed #999}}
</style></head><body>
<div class="sticker">
  <div class="name">{$name}</div>
  <div class="row"><span class="meta">كود الصنف</span><strong>{$code}</strong></div>
  <div class="row"><span class="meta">الرقم المميز</span><strong class="uid">{$uid}</strong></div>
  <div class="row"><span class="meta">الوزن</span><strong>{$weight} KG</strong></div>
  <div class="row"><span class="meta">تاريخ الإنتاج</span><strong>{$prod}</strong></div>
  <div class="row"><span class="meta">الصلاحية</span><strong>{$days} يوم / {$expiry}</strong></div>
  <div class="qr"><img src="{$qrUrl}" alt="QR" width="140" height="140"><div class="meta">{$uid}</div></div>
</div>
<script>window.print()</script>
</body></html>
HTML;
    }
}

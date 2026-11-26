<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>ستيكر علبة #{{ $box->sn }} — BY RASLAN</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; margin: 0; background: #f1f5f9; color: #0f172a; }
        .sticker { width: 320px; margin: 24px auto; background: #fff; border: 2px solid #0f172a; border-radius: 12px; padding: 16px; text-align: center; }
        .brand { font-weight: 900; font-size: 20px; letter-spacing: 1px; }
        .name { font-weight: 800; font-size: 22px; margin: 8px 0 4px; }
        .row { display: flex; justify-content: space-between; font-size: 15px; padding: 4px 0; border-top: 1px dashed #cbd5e1; }
        .row span:first-child { color: #64748b; }
        .row span:last-child { font-weight: 700; }
        .sn-label { margin-top: 10px; font-size: 13px; color: #64748b; }
        .sn { font-size: 44px; font-weight: 900; line-height: 1.1; direction: ltr; }
        .note { margin-top: 10px; font-size: 12px; color: #64748b; }
        .actions { text-align: center; margin: 16px; }
        .actions button { font-size: 16px; padding: 10px 24px; border-radius: 8px; border: none; background: #0f766e; color: #fff; cursor: pointer; }
        @media print {
            body { background: #fff; }
            .sticker { margin: 0; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="sticker">
        <div class="brand">BY RASLAN</div>
        <div class="name">{{ $box->item?->name ?? '—' }}</div>
        <div class="row"><span>كود الصنف</span><span dir="ltr">{{ $box->item?->scale_plu ?? '—' }}</span></div>
        <div class="row"><span>الوزن</span><span>{{ number_format((float) $box->weight, 3) }} كجم</span></div>
        <div class="row"><span>الموقع</span><span>{{ $box->location?->name ?? $box->location_id }}</span></div>
        <div class="row"><span>تاريخ الإنتاج</span><span>{{ $box->packed_at?->format('Y-m-d') ?? '—' }}</span></div>
        <div class="row"><span>تاريخ الصلاحية</span><span>{{ $box->expires_at?->format('Y-m-d') ?? '—' }}</span></div>
        <div class="sn-label">الرقم المميز</div>
        <div class="sn">{{ $box->sn }}</div>
        <div class="note">الرقم المميز مسلسل عالمي ولا يتكرر أبداً. لطباعة باركود مقروء بالماسح يُضاف لاحقاً خط/مكتبة باركود — الرقم مطبوع هنا نصاً.</div>
    </div>
    <div class="actions">
        <button type="button" onclick="window.print()">طباعة الستيكر</button>
    </div>
</body>
</html>

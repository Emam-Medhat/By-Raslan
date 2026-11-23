<?php

namespace App\Modules\Purchasing\Services;

use App\Models\User;
use App\Modules\Inventory\Models\StockMove;
use App\Modules\Purchasing\Models\Lot;
use App\Modules\Purchasing\Models\PoLine;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\Receipt;
use App\Modules\Purchasing\Models\ReceiptCheck;
use App\Modules\Purchasing\Models\ReceiptDoc;
use App\Modules\Purchasing\Models\ReceiptLine;
use App\Modules\Purchasing\Models\SupplierPayment;
use Illuminate\Support\Facades\DB;

class PurchasingService
{
    public function __construct(private readonly SpecService $specs)
    {
    }
    public function createPo(int $supplierId, ?string $expected, ?string $note, array $lines, ?string $by): PurchaseOrder
    {
        return DB::transaction(function () use ($supplierId, $expected, $note, $lines, $by) {
            $po = PurchaseOrder::create([
                'no' => (int) max(300, (int) PurchaseOrder::max('no')) + 1,
                'supplier_id' => $supplierId,
                'status' => 'open',
                'expected' => $expected,
                'note' => $note,
                'created_by' => $by,
            ]);

            $index = 0;
            foreach ($lines as $line) {
                $index++;
                PoLine::create([
                    'po_id' => $po->id,
                    'line' => $index,
                    'item_id' => (int) $line['item_id'],
                    'qty' => (float) $line['qty'],
                    'price' => (float) ($line['price'] ?? 0),
                    'received' => 0,
                ]);
            }

            return $po;
        });
    }

    public function cancelPo(PurchaseOrder $po): void
    {
        if (! in_array($po->status, ['open', 'partial'], true)) {
            throw new \RuntimeException('أمر التوريد ده مش مفتوح');
        }

        $po->update(['status' => 'cancelled']);
    }

    /**
     * Receive a PO: create lots + stock intake per accepted line.
     *
     * @param  list<array{line:int,weighed:float,rejected?:float,price?:float,note?:string}>  $lines
     * @return array{receipt:Receipt,flags:array<int,string>}
     */
    public function receivePo(int $poId, array $lines, ?string $invoice, ?string $by, ?int $overrideId = null, ?string $reason = null): array
    {
        return DB::transaction(function () use ($poId, $lines, $invoice, $by, $overrideId, $reason) {
            $po = PurchaseOrder::where('id', $poId)->lockForUpdate()->firstOrFail();

            if (! in_array($po->status, ['open', 'partial'], true)) {
                throw new \RuntimeException('أمر التوريد ده مش مفتوح');
            }

            $receipt = Receipt::create([
                'no' => (int) max(1100, (int) Receipt::max('no')) + 1,
                'po_id' => $po->id,
                'supplier_id' => $po->supplier_id,
                'result' => 'مقبول',
                'invoice_no' => $invoice,
                'by' => $by,
            ]);

                        $flags = [];
            $violations = [];
            $violationValue = 0.0;
            $acceptedCount = 0;

            foreach ($lines as $line) {
                $lineNo = (int) ($line['line'] ?? 0);
                $poLine = PoLine::with('item')->where('po_id', $poId)->where('line', $lineNo)->first();
                if (! $poLine) { throw new \RuntimeException('سطر مش في أمر التوريد'); }
                $weighed = (float) ($line['weighed'] ?? 0);
                if ($weighed <= 0) { continue; }
                $rejected = (float) ($line['rejected'] ?? 0);
                if ($rejected > $weighed) { throw new \RuntimeException('المرفوض أكتر من اللي اتوزن'); }
                $accepted = $weighed - $rejected;
                $price = (float) ($line['price'] ?? $poLine->price);
                $spec = $this->specs->current($poLine->item_id);
                $checks = $line['checks'] ?? [];
                $docs = $line['docs'] ?? [];
                $itemName = $poLine->item?->name ?? ('#'.$poLine->item_id);

                if ($spec && $accepted > 0) {
                    $snapshot = $spec->snapshot ?? [];
                    $requiredChecks = count($snapshot['checks'] ?? []);
                    $requiredCerts = count($snapshot['certs'] ?? []);
                    if (count($checks) < $requiredChecks) { throw new \RuntimeException($itemName.': جاوب على كل بنود الفحص'); }
                    if (count($docs) < $requiredCerts) { throw new \RuntimeException($itemName.': صوّر كل مستند أو علّم إنه مش موجود'); }
                    if (isset($snapshot['temp_max']) && ($line['temp'] ?? null) === null) { throw new \RuntimeException($itemName.': اكتب الحرارة'); }
                    if (isset($snapshot['temp_max']) && (float) ($line['temp'] ?? 0) > (float) $snapshot['temp_max']) {
                        $violations[] = $itemName.' — الحرارة '.($line['temp']).'° والحد '.$snapshot['temp_max'].'°';
                        $violationValue += $accepted * $price;
                    }
                    if (! empty($snapshot['min_shelf_days']) && ! empty($line['expires'])) {
                        $days = now()->startOfDay()->diffInDays(\Illuminate\Support\Carbon::parse($line['expires'])->startOfDay(), false);
                        if ($days < (int) $snapshot['min_shelf_days']) {
                            $violations[] = $itemName.' — الصلاحية '.$days.' يوم والمطلوب '.$snapshot['min_shelf_days'];
                            $violationValue += $accepted * $price;
                        }
                    }
                    foreach ($checks as $c) { if (! ($c['ok'] ?? true)) { $violations[] = $itemName.' — مش مطابق: '.($c['t'] ?? $c['text'] ?? 'بند'); $violationValue += $accepted * $price; } }
                    foreach ($docs as $d) { if ($d['missing'] ?? false) { $violations[] = $itemName.' — من غير '.($d['n'] ?? $d['name'] ?? 'مستند'); $violationValue += $accepted * $price; } }
                } elseif (! $spec && $accepted > 0) {
                    $violations[] = $itemName.' — المواصفة مش معتمدة من الجودة';
                    $violationValue += $accepted * $price;
                }
            }

            $violations = array_values(array_unique($violations));
            $ov = null;
            if ($violations !== []) {
                if ($overrideId) {
                    $ov = \App\Modules\Purchasing\Models\OverrideRequest::where('id', $overrideId)->where('kind', 'recv')->where('ref', 'PO-'.$po->no)->where('status', 'approved')->first();
                    if (! $ov) { throw new \RuntimeException('التجاوز ده مش متوافق عليه'); }
                    $ov->update(['status' => 'used']);
                } else {
                    $user = $by ? \App\Models\User::find($by) : null;
                    $canDirect = $user && $user->can('override.recv') && ($user->permissionLimit('override.recv') === null || $violationValue <= $user->permissionLimit('override.recv'));
                    if ($canDirect && is_string($reason) && mb_strlen(trim($reason)) >= 5) {
                        $ov = \App\Modules\Purchasing\Models\OverrideRequest::create(['kind' => 'recv', 'ref' => 'PO-'.$po->no, 'violations' => $violations, 'value' => $violationValue, 'reason' => trim($reason), 'status' => 'used', 'decided_by' => $by, 'decided_at' => now(), 'decision_note' => 'تجاوز مباشر وقت الاستلام', 'created_by' => $by]);
                    } else {
                        throw new \RuntimeException('OVERRIDE_REQUIRED::'.json_encode(['violations' => $violations, 'value' => $violationValue], JSON_UNESCAPED_UNICODE));
                    }
                }
                $flags[] = 'استلام بتجاوز: '.($ov?->reason ?? $reason);
            }

            foreach ($lines as $line) {
                $lineNo = (int) ($line['line'] ?? 0);
                $poLine = PoLine::with('item')->where('po_id', $poId)->where('line', $lineNo)->first();
                if (! $poLine) { continue; }
                $weighed = (float) ($line['weighed'] ?? 0);
                if ($weighed <= 0) { continue; }
                $rejected = (float) ($line['rejected'] ?? 0);
                $accepted = $weighed - $rejected;
                $price = (float) ($line['price'] ?? $poLine->price);
                $lotCode = null;
                $spec = $this->specs->current($poLine->item_id);
                $specVersion = $spec?->version;
                $checks = $line['checks'] ?? [];
                $docs = $line['docs'] ?? [];

                if ($accepted > 0) {
                    $lotCode = sprintf('RM-%d-%s-%d-%d', $poLine->item_id, now()->format('ymd'), $receipt->no, $lineNo);

                    Lot::create([
                        'code' => $lotCode,
                        'item_id' => $poLine->item_id,
                        'receipt_id' => $receipt->id,
                        'supplier_id' => $po->supplier_id,
                        'qty' => $accepted,
                        'cost' => $price,
                        'location_id' => 'main',
                    ]);

                    StockMove::create([
                        'item_id' => $poLine->item_id,
                        'location_id' => 'main',
                        'qty' => $accepted,
                        'kind' => 'receive',
                        'reason' => 'استلام PO-'.$po->no,
                        'ref' => 'GRN-'.$receipt->no,
                        'unit_cost' => $price,
                        'by' => $by,
                    ]);

                    $acceptedCount++;
                }

                ReceiptLine::create([
                    'receipt_id' => $receipt->id,
                    'line' => $lineNo,
                    'item_id' => $poLine->item_id,
                    'weighed' => $weighed,
                    'rejected' => $rejected,
                    'accepted' => $accepted,
                    'price' => $price,
                    'temp' => isset($line['temp']) && $line['temp'] !== '' ? (float) $line['temp'] : null,
                    'expires' => ! empty($line['expires']) ? $line['expires'] : null,
                    'spec_version' => $specVersion,
                    'lot_code' => $lotCode,
                    'note' => $line['note'] ?? null,
                ]);

                foreach ($checks as $check) {
                    ReceiptCheck::create([
                        'receipt_id' => $receipt->id,
                        'item_id' => $poLine->item_id,
                        'check_text' => $check['t'] ?? ($check['text'] ?? 'بند'),
                        'ok' => (bool) ($check['ok'] ?? true),
                        'created_at' => now(),
                    ]);
                }

                foreach ($docs as $doc) {
                    ReceiptDoc::create([
                        'receipt_id' => $receipt->id,
                        'item_id' => $poLine->item_id,
                        'doc_name' => $doc['n'] ?? ($doc['name'] ?? 'مستند'),
                        'photo' => $doc['photo'] ?? null,
                        'missing' => (bool) ($doc['missing'] ?? false),
                        'created_at' => now(),
                    ]);
                }

                $poLine->update(['received' => $poLine->received + $accepted]);

                if (abs($price - (float) $poLine->price) > 0.0001) {
                    $flags[] = "سعر الصنف {$poLine->item_id} بە {$price} بدل {$poLine->price}";
                }
            }

            $po->refresh()->load('lines');
            $allDone = $po->lines->every(fn ($pl) => $pl->received >= $pl->qty * 0.99);
            $po->update(['status' => $allDone ? 'done' : 'partial']);

            $result = $acceptedCount === 0 ? 'مرفوض' : ($flags !== [] ? 'مقبول جزئي' : 'مقبول');
            $receipt->update(['result' => $result, 'flags' => $flags, 'override_id' => $ov?->id]);

            return ['receipt' => $receipt, 'flags' => $flags];
        });
    }

    public function paySupplier(User $user, int $supplierId, float $amount, string $method, ?string $ref, ?string $note): SupplierPayment
    {
        if ($amount <= 0) {
            throw new \RuntimeException('المبلغ لازم أكبر من صفر');
        }

        $limit = $user->permissionLimit('acc.pay');
        if ($limit !== null && $amount > $limit) {
            throw new \RuntimeException('المبلغ أكبر من حدّك في الدفع');
        }

        return SupplierPayment::create([
            'no' => (int) max(7000, (int) SupplierPayment::max('no')) + 1,
            'supplier_id' => $supplierId,
            'amount' => $amount,
            'method' => $method,
            'ref' => $ref,
            'note' => $note,
            'by' => $user->id,
        ]);
    }
}
<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Item;
use App\Modules\Catalog\Models\ItemBarcode;

class ScaleBarcodeService
{
    /**
     * Parse scale barcode. Default supported format (from manager example):
     * - item code: 000110 (6 digits)
     * - weight: 00460 (5 digits) => 00 kg + 460 g = 0.460 KG
     * Also supports generic "item|weight" and plain codes.
     *
     * @return array{item:Item,weight:float,scale_code:string,raw:string}
     */
    public function parse(string $code): array
    {
        $raw = trim($code);
        $digits = preg_replace('/\D+/', '', $raw);

        // format: item|weight
        if (preg_match('/^(\d+)\|([\d.]+)$/', $raw, $m)) {
            $item = $this->resolveItem($m[1]);
            $weight = (float) $m[2];
            return $this->result($item, $weight, $raw, $raw);
        }

        // EAN-13 weight-embedded common format: PP IIIII WWWWW C (2 prefix,5 item,5 weight,1 check)
        // We try flexible matching: look up item by 5/6 digit candidate and read 5-digit weight.
        if (strlen($digits) === 13) {
            $candidates = [
                ['item' => substr($digits, 2, 5), 'weight' => substr($digits, 7, 5)],
                ['item' => substr($digits, 2, 6), 'weight' => substr($digits, 8, 4)], // fallback
                ['item' => substr($digits, 1, 6), 'weight' => substr($digits, 7, 5)],
            ];
            foreach ($candidates as $c) {
                $item = $this->findItem($c['item']);
                if ($item) {
                    $weight = $this->parseWeight($c['weight']);
                    if ($weight > 0) return $this->result($item, $weight, $raw, $raw);
                }
            }
        }

        // Manager example: 00011000460? Or separate fields; accept 11/12/13 digit payloads containing 6-digit item+5-digit weight.
        if (strlen($digits) >= 11) {
            $itemCode = substr($digits, 0, 6);
            $weightDigits = substr($digits, 6, 5);
            $item = $this->findItem($itemCode);
            if ($item) {
                $weight = $this->parseWeight($weightDigits);
                if ($weight > 0) return $this->result($item, $weight, $raw, $raw);
            }
            // try without leading zeros in item code
            $item = $this->findItem(ltrim($itemCode, '0'));
            if ($item) {
                $weight = $this->parseWeight($weightDigits);
                if ($weight > 0) return $this->result($item, $weight, $raw, $raw);
            }
        }

        // Fallback: treat as item barcode / PLU, weight unknown
        $item = $this->findItem($digits) ?: $this->findItem($raw);
        if ($item) {
            throw new \RuntimeException('تم التعرف على الصنف لكن وزن الطبق غير موجود في الباركود.');
        }

        throw new \RuntimeException('باركود الميزان غير معروف أو الصنف غير مسجل.');
    }

    private function parseWeight(string $digits): float
    {
        $d = preg_replace('/\D+/', '', $digits);
        if ($d === '') return 0.0;
        if (strlen($d) === 5) {
            $kg = (int) substr($d, 0, 2);
            $g = (int) substr($d, 2, 3);
            return round($kg + ($g / 1000), 3);
        }
        if (strlen($d) === 4) { // grams only / 4-digit
            return round(((int) $d) / 1000, 3);
        }
        // fallback: treat as grams
        $n = (int) $d;
        return $n > 0 ? round($n / 1000, 3) : 0.0;
    }

    private function findItem(string $code): ?Item
    {
        $code = trim($code);
        if ($code === '') return null;
        $item = Item::where('active', true)->where('scale_plu', $code)->first();
        if ($item) return $item;
        $barcode = ItemBarcode::where('code', $code)->first();
        if ($barcode) return Item::where('id', $barcode->item_id)->where('active', true)->first();
        // also try padded variants
        $padded = str_pad(ltrim($code, '0') ?: '0', 6, '0', STR_PAD_LEFT);
        if ($padded !== $code) {
            $item = Item::where('active', true)->where('scale_plu', $padded)->first();
            if ($item) return $item;
        }
        return null;
    }

    private function resolveItem(string $code): Item
    {
        $item = $this->findItem($code);
        if (! $item && ctype_digit($code)) {
            $item = Item::where('active', true)->find((int) $code);
        }
        if (! $item) throw new \RuntimeException('كود الصنف غير مسجل في النظام: ' . $code);
        return $item;
    }

    private function result(Item $item, float $weight, string $scaleCode, string $raw): array
    {
        if ($weight <= 0) throw new \RuntimeException('وزن الطبق غير صحيح.');
        return ['item' => $item, 'weight' => round($weight, 3), 'scale_code' => $scaleCode, 'raw' => $raw];
    }
}

<?php

namespace App\Modules\Production\Services;

/**
 * تحليل ملصق الميزان (باركود الميزان الطويل).
 *
 * الصيغة المدعومة — باركود ميزان 13 رقماً (نمط EAN-13):
 *
 *   رقم 1        : مهمل (علامة/رقم فحص)
 *   أرقام 2-7    : كود الصنف — 6 أرقام، بالأصفار البادئة (مثال: 000110)
 *   أرقام 8-12   : الوزن — 5 أرقام: أول رقمين كيلوهات، وآخر 3 أرقام جرامات
 *                  (مثال: 00460 = 0.460 كجم)
 *   رقم 13       : مهمل (رقم فحص)
 *
 * مثال كامل: 2 + 000110 + 00460 + 5 = 2000110004605
 *   → كود الصنف 000110، الوزن 0.460 كجم.
 *
 * المطابقة: كود الصنف هو الذي يُطابَق به الصنف في السيستم (حقل
 * items.scale_plu) — الاسم لا يُطابَق، الرقم فقط. لذلك يجب إدخال نفس
 * الكود في الميزان وفي السيستم.
 *
 * صيغة قديمة (للتوافق): "itemId|weight" مثل "5|12.5" — وتُعاد بنفس
 * شكل النتيجة، مع item_code = null و item_id = رقم الصنف الداخلي.
 *
 * النتيجة: مصفوفة فيها:
 *   raw       : النص الأصلي كما مُسح
 *   source    : 'scale' أو 'legacy' أو null عند الخطأ
 *   item_code : كود الصنف 6 أرقام (أو null في الصيغة القديمة)
 *   item_id   : رقم الصنف الداخلي (في الصيغة القديمة فقط، وإلا null)
 *   weight_kg : الوزن بالكيلوجرام (3 خانات عشرية)
 *   error     : رسالة خطأ بالعربية أو null عند النجاح
 */
class ScaleBarcodeParser
{
    public static function parse(string $raw): array
    {
        $code = trim($raw);
        $base = ['raw' => $raw, 'source' => null, 'item_code' => null, 'item_id' => null, 'weight_kg' => 0.0, 'error' => null];

        if ($code === '') {
            $base['error'] = 'امسح ملصق الميزان الأول';

            return $base;
        }

        // الصيغة القديمة: "itemId|weight" مثل "5|12.5".
        if (str_contains($code, '|')) {
            if (! preg_match('/^(\d+)\|([\d.]+)$/', $code, $m)) {
                $base['error'] = 'صيغة ملصق الميزان غير صحيحة';

                return $base;
            }

            $weight = round((float) $m[2], 3);
            if ($weight <= 0) {
                $base['error'] = 'وزن العلبة غير صحيح';

                return $base;
            }

            $base['source'] = 'legacy';
            $base['item_id'] = (int) $m[1];
            $base['weight_kg'] = $weight;

            return $base;
        }

        // باركود الميزان: 13 رقماً فقط.
        if (! ctype_digit($code) || strlen($code) !== 13) {
            $base['error'] = 'باركود الميزان لازم يكون 13 رقم — الأول والآخر مهمشين، وبعدهم كود الصنف 6 أرقام ثم الوزن 5 أرقام';

            return $base;
        }

        $itemCode = substr($code, 1, 6);
        $kilos = (int) substr($code, 7, 2);
        $grams = (int) substr($code, 9, 3);
        $weight = round($kilos + $grams / 1000, 3);

        if ($weight <= 0) {
            $base['error'] = 'وزن العلبة غير صحيح — الوزن في الباركود صفر';

            return $base;
        }

        $base['source'] = 'scale';
        $base['item_code'] = $itemCode;
        $base['weight_kg'] = $weight;

        return $base;
    }

    /** مثل parse() لكن يرمي RuntimeException برسالة الخطأ العربية. */
    public static function parseOrFail(string $raw): array
    {
        $parsed = self::parse($raw);

        if ($parsed['error'] !== null) {
            throw new \RuntimeException($parsed['error']);
        }

        return $parsed;
    }
}

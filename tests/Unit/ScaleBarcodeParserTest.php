<?php

namespace Tests\Unit;

use App\Modules\Production\Services\ScaleBarcodeParser;
use PHPUnit\Framework\TestCase;

class ScaleBarcodeParserTest extends TestCase
{
    public function test_parses_scale_barcode_with_item_code_and_weight(): void
    {
        // 2 | 000110 | 00460 | 5  →  كود الصنف 000110، الوزن 0.460 كجم
        $parsed = ScaleBarcodeParser::parse('2000110004605');

        $this->assertNull($parsed['error']);
        $this->assertSame('scale', $parsed['source']);
        $this->assertSame('000110', $parsed['item_code']);
        $this->assertSame(0.46, $parsed['weight_kg']);
        $this->assertSame('2000110004605', $parsed['raw']);
    }

    public function test_parses_another_code_and_weight_over_one_kilo(): void
    {
        // 2 | 000201 | 01250 | 9  →  كود الصنف 000201، الوزن 1.250 كجم
        $parsed = ScaleBarcodeParser::parse('2000201012509');

        $this->assertNull($parsed['error']);
        $this->assertSame('000201', $parsed['item_code']);
        $this->assertSame(1.25, $parsed['weight_kg']);
    }

    public function test_ignores_first_and_last_digits(): void
    {
        // نفس الكود والوزن مع أول وآخر رقم مختلفين
        $parsed = ScaleBarcodeParser::parse('9000110004601');

        $this->assertNull($parsed['error']);
        $this->assertSame('000110', $parsed['item_code']);
        $this->assertSame(0.46, $parsed['weight_kg']);
    }

    public function test_rejects_invalid_length(): void
    {
        $parsed = ScaleBarcodeParser::parse('200011000460');

        $this->assertNotNull($parsed['error']);
        $this->assertNull($parsed['source']);
    }

    public function test_rejects_non_digits(): void
    {
        $parsed = ScaleBarcodeParser::parse('20001100046A5');

        $this->assertNotNull($parsed['error']);
    }

    public function test_rejects_zero_weight(): void
    {
        // 2 | 000110 | 00000 | 5  →  الوزن صفر
        $parsed = ScaleBarcodeParser::parse('2000110000005');

        $this->assertNotNull($parsed['error']);
        $this->assertSame(0.0, $parsed['weight_kg']);
    }

    public function test_rejects_empty_barcode(): void
    {
        $parsed = ScaleBarcodeParser::parse('  ');

        $this->assertNotNull($parsed['error']);
    }

    public function test_accepts_legacy_pipe_format(): void
    {
        $parsed = ScaleBarcodeParser::parse('5|12.5');

        $this->assertNull($parsed['error']);
        $this->assertSame('legacy', $parsed['source']);
        $this->assertSame(5, $parsed['item_id']);
        $this->assertNull($parsed['item_code']);
        $this->assertSame(12.5, $parsed['weight_kg']);
    }

    public function test_parse_or_fail_throws_with_arabic_message(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('باركود الميزان');

        ScaleBarcodeParser::parseOrFail('123');
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Verification\VerificationCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificationCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_returns_twelve_char_base32_code(): void
    {
        $code = VerificationCode::generate();

        $this->assertSame(12, strlen($code));
        $this->assertMatchesRegularExpression('/^[2-9A-HJ-NP-Z]{12}$/', $code);
    }

    public function test_generate_produces_unique_codes(): void
    {
        $codes = array_map(
            static fn (): string => VerificationCode::generate(),
            range(1, 10),
        );

        $this->assertCount(10, array_unique($codes));
    }

    public function test_normalize_strips_separators_and_uppercases(): void
    {
        $this->assertSame('ABCDEFGHIJKL', VerificationCode::normalize('abcd-efgh-ijkl'));
        $this->assertSame('ABCDEFGHIJKL', VerificationCode::normalize(' ABCD EFGH IJKL '));
        $this->assertSame('K7M29QXR4TZN', VerificationCode::normalize('k7m2-9qxr-4tzn'));
    }

    public function test_display_groups_twelve_char_code_into_four_char_blocks(): void
    {
        $this->assertSame('ABCD-EFGH-IJKL', VerificationCode::display('ABCDEFGHIJKL'));
    }

    public function test_display_keeps_legacy_codes_untouched(): void
    {
        $legacy = str_repeat('ab12', 16);

        $this->assertSame($legacy, VerificationCode::display($legacy));
    }
}

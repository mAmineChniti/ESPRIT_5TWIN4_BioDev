<?php

namespace Tests\Unit;

use App\LocalizedNumber;
use Tests\TestCase;

class LocalizedNumberTest extends TestCase
{
    public function test_english_keeps_western_digits_and_dot_decimals(): void
    {
        app()->setLocale('en');

        $this->assertSame('1,234.5', LocalizedNumber::format(1234.5, 1));
        $this->assertSame('100', LocalizedNumber::format(100));
    }

    public function test_french_uses_comma_decimals_and_space_grouping(): void
    {
        app()->setLocale('fr');

        $this->assertSame('1 234,5', LocalizedNumber::format(1234.5, 1));
        $this->assertSame('100', LocalizedNumber::format(100));
    }

    public function test_arabic_uses_eastern_digits(): void
    {
        app()->setLocale('ar');

        $this->assertSame('١٬٢٣٤٫٥', LocalizedNumber::format(1234.5, 1));
        $this->assertSame('١٠٠', LocalizedNumber::format(100));
    }
}

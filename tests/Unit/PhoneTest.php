<?php

namespace Tests\Unit;

use App\Support\Phone;
use Tests\TestCase;

class PhoneTest extends TestCase
{
    public function test_to_whatsapp_normalizes_leading_zero(): void
    {
        $this->assertSame('60123456789', Phone::toWhatsApp('0123456789'));
    }

    public function test_to_whatsapp_keeps_country_code(): void
    {
        $this->assertSame('60123456789', Phone::toWhatsApp('60123456789'));
    }

    public function test_to_whatsapp_strips_formatting(): void
    {
        $this->assertSame('60123456789', Phone::toWhatsApp('012-345 6789'));
    }

    public function test_to_whatsapp_handles_plus_prefix(): void
    {
        $this->assertSame('60123456789', Phone::toWhatsApp('+60123456789'));
    }

    public function test_to_whatsapp_blank_is_null(): void
    {
        $this->assertNull(Phone::toWhatsApp(''));
        $this->assertNull(Phone::toWhatsApp(null));
    }

    public function test_to_tel_is_e164(): void
    {
        $this->assertSame('+60123456789', Phone::toTel('0123456789'));
    }

    public function test_digits_strips_non_digits(): void
    {
        $this->assertSame('0123456789', Phone::digits('012-345 6789'));
    }
}

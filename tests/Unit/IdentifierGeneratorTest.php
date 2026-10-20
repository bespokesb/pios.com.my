<?php

namespace Tests\Unit;

use App\Models\Partner;
use App\Services\IdentifierGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdentifierGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_unique_partner_identifiers(): void
    {
        $generator = app(IdentifierGenerator::class);

        $first = $generator->generate('partner', Partner::class, 'partner_no');
        $second = $generator->generate('partner', Partner::class, 'partner_no');

        $this->assertSame('P000001', $first);
        $this->assertSame('P000002', $second);
        $this->assertNotSame($first, $second);
    }
}

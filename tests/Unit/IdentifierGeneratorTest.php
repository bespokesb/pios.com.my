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

    public function test_fills_gap_when_record_in_middle_is_deleted(): void
    {
        $generator = app(IdentifierGenerator::class);

        $p1 = Partner::query()->create(['partner_no' => 'P000001', 'applicant_name' => 'P1']);
        $p2 = Partner::query()->create(['partner_no' => 'P000002', 'applicant_name' => 'P2']);
        $p3 = Partner::query()->create(['partner_no' => 'P000003', 'applicant_name' => 'P3']);
        $p4 = Partner::query()->create(['partner_no' => 'P000004', 'applicant_name' => 'P4']);

        // Delete P000003
        $p3->delete();

        // Next generated ID must fill the gap and return P000003
        $next = $generator->generate('partner', Partner::class, 'partner_no');
        $this->assertSame('P000003', $next);
    }

    public function test_reuses_number_when_latest_record_is_deleted(): void
    {
        $generator = app(IdentifierGenerator::class);

        $p1 = Partner::query()->create(['partner_no' => 'P000001', 'applicant_name' => 'P1']);
        $p2 = Partner::query()->create(['partner_no' => 'P000002', 'applicant_name' => 'P2']);
        $p3 = Partner::query()->create(['partner_no' => 'P000003', 'applicant_name' => 'P3']);

        // Delete P000003
        $p3->delete();

        // Next generated ID must not skip to P000004, but reuse P000003
        $next = $generator->generate('partner', Partner::class, 'partner_no');
        $this->assertSame('P000003', $next);
    }
}

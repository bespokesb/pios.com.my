<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\BusinessOwners\Pages\EditBusinessOwner;
use App\Filament\Resources\BusinessOwners\Pages\ListBusinessOwners;
use App\Filament\Resources\Partners\Pages\EditPartner;
use App\Models\BusinessOwner;
use App\Models\Partner;
use App\Models\User;
use App\Services\WhatsAppPassSender;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class WhatsAppPassSenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_pass_sender_generates_valid_wa_url_for_business_owner(): void
    {
        $partner = Partner::query()->create([
            'applicant_name' => 'Partner Sample',
            'partner_no' => 'P000111',
            'status' => 'active',
        ]);

        $bo = BusinessOwner::query()->create([
            'partner_id' => $partner->id,
            'applicant_name' => 'Ali Abu',
            'business_owner_no' => 'BO000123',
            'email' => 'ali@example.com',
            'hp_no' => '0123456789',
            'status' => 'active',
        ]);

        $url = WhatsAppPassSender::forBusinessOwner($bo, 'secretPass123');

        $this->assertNotNull($url);
        $this->assertStringStartsWith('https://wa.me/60123456789?text=', $url);

        $decodedText = urldecode(parse_url($url, PHP_URL_QUERY));
        $this->assertStringContainsString('Ali Abu', $decodedText);
        $this->assertStringContainsString('ali@example.com', $decodedText);
        $this->assertStringContainsString('secretPass123', $decodedText);
        $this->assertStringContainsString('/login', $decodedText);
    }

    public function test_whatsapp_pass_sender_generates_valid_wa_url_for_partner(): void
    {
        $partner = Partner::query()->create([
            'applicant_name' => 'Siti Nur',
            'partner_no' => 'P000456',
            'email' => 'siti@example.com',
            'hp_no' => '+60198765432',
            'status' => 'active',
        ]);

        $url = WhatsAppPassSender::forPartner($partner, 'partnerPass456');

        $this->assertNotNull($url);
        $this->assertStringStartsWith('https://wa.me/60198765432?text=', $url);

        $decodedText = urldecode(parse_url($url, PHP_URL_QUERY));
        $this->assertStringContainsString('Siti Nur', $decodedText);
        $this->assertStringContainsString('siti@example.com', $decodedText);
        $this->assertStringContainsString('partnerPass456', $decodedText);
        $this->assertStringContainsString('/login', $decodedText);
    }

    public function test_edit_business_owner_page_send_pass_whatsapp_header_action_updates_password(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
        ]);

        $user = User::factory()->create([
            'email' => 'bo-header@example.com',
            'password' => Hash::make('old-pass'),
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
        ]);

        $partner = Partner::query()->create([
            'applicant_name' => 'Sponsoring Partner',
            'partner_no' => 'P000999',
            'status' => 'active',
        ]);

        $bo = BusinessOwner::query()->create([
            'user_id' => $user->id,
            'partner_id' => $partner->id,
            'applicant_name' => 'BO Header',
            'business_owner_no' => 'BO000888',
            'email' => 'bo-header@example.com',
            'hp_no' => '0129876543',
            'status' => 'active',
        ]);

        $this->actingAs($admin);

        Livewire::test(EditBusinessOwner::class, ['record' => $bo->id])
            ->callAction('sendPassWhatsApp', [
                'password' => 'new-wa-pass-111',
            ])
            ->assertNotified('Kata Laluan Dikemaskini');

        $user->refresh();
        $this->assertTrue(Hash::check('new-wa-pass-111', $user->password));
    }

    public function test_edit_partner_page_send_pass_whatsapp_header_action_updates_password(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
        ]);

        $partnerUser = User::factory()->create([
            'email' => 'partner-header@example.com',
            'password' => Hash::make('old-partner-pass'),
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $partner = Partner::query()->create([
            'user_id' => $partnerUser->id,
            'applicant_name' => 'Partner Header',
            'partner_no' => 'P000777',
            'email' => 'partner-header@example.com',
            'hp_no' => '0139876543',
            'status' => 'active',
        ]);

        $this->actingAs($admin);

        Livewire::test(EditPartner::class, ['record' => $partner->id])
            ->callAction('sendPassWhatsApp', [
                'password' => 'new-wa-pass-222',
            ])
            ->assertNotified('Kata Laluan Dikemaskini');

        $partnerUser->refresh();
        $this->assertTrue(Hash::check('new-wa-pass-222', $partnerUser->password));
    }

    public function test_business_owners_table_send_pass_action_updates_password(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
        ]);

        $user = User::factory()->create([
            'email' => 'table-bo@example.com',
            'password' => Hash::make('old-table-pass'),
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
        ]);

        $partner = Partner::query()->create([
            'applicant_name' => 'Partner For Table',
            'partner_no' => 'P000555',
            'status' => 'active',
        ]);

        $bo = BusinessOwner::query()->create([
            'user_id' => $user->id,
            'partner_id' => $partner->id,
            'applicant_name' => 'BO In Table',
            'business_owner_no' => 'BO000555',
            'email' => 'table-bo@example.com',
            'hp_no' => '0121112233',
            'status' => 'active',
        ]);

        $this->actingAs($admin);

        Livewire::test(ListBusinessOwners::class)
            ->callAction(TestAction::make('sendPassWhatsApp')->table($bo), [
                'password' => 'table-pass-333',
            ])
            ->assertNotified('Kata Laluan Dikemaskini');

        $user->refresh();
        $this->assertTrue(Hash::check('table-pass-333', $user->password));
    }
}

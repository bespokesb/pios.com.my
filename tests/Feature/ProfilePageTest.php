<?php

namespace Tests\Feature;

use App\Enums\RecordStatus;
use App\Models\BusinessOwner;
use App\Models\Partner;
use Filament\Auth\Pages\EditProfile;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

class ProfilePageTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Filament::setCurrentPanel(null);
        parent::tearDown();
    }

    public function test_every_panel_exposes_a_profile_route(): void
    {
        $uris = collect(Route::getRoutes())->map(fn ($r): string => $r->uri())->all();

        foreach (['admin', 'partner', 'business'] as $panel) {
            $this->assertContains("{$panel}/profile", $uris);
        }
    }

    public function test_partner_can_open_and_update_their_own_profile(): void
    {
        $partner = Partner::factory()->create(['status' => RecordStatus::Active]);
        $user = $partner->user;

        Filament::setCurrentPanel('partner');
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm(['name' => 'Renamed Partner'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Renamed Partner', $user->fresh()->name);
    }

    public function test_business_owner_can_open_their_profile(): void
    {
        $bo = BusinessOwner::factory()->create();

        Filament::setCurrentPanel('business');
        $this->actingAs($bo->user);

        Livewire::test(EditProfile::class)
            ->assertOk();
    }
}

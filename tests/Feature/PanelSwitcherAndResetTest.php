<?php

namespace Tests\Feature;

use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\BusinessOwner;
use App\Models\Partner;
use App\Models\User;
use BezhanSalleh\PanelSwitch\PanelSwitch;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PanelSwitcherAndResetTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Filament::setCurrentPanel(null);
        parent::tearDown();
    }

    public function test_password_reset_routes_exist_for_every_panel(): void
    {
        $uris = collect(Route::getRoutes())->map(fn ($r): string => $r->uri())->all();

        foreach (['admin', 'partner', 'business'] as $panel) {
            $this->assertContains("{$panel}/password-reset/request", $uris);
            $this->assertContains("{$panel}/password-reset/reset", $uris);
        }
    }

    public function test_switcher_offers_other_accessible_panels_to_a_dual_role_user(): void
    {
        [$user] = $this->dualRole();
        $this->actingAs($user);
        Filament::setCurrentPanel('partner');

        // The bezhanSalleh panel switch auto-lists only panels the user can access.
        $this->assertEqualsCanonicalizing(['partner', 'business'], $this->switchablePanelIds());
    }

    public function test_switcher_offers_all_panels_to_an_admin(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SysAdmin, 'status' => UserStatus::Active]);
        $this->actingAs($admin);
        Filament::setCurrentPanel('admin');

        $this->assertEqualsCanonicalizing(['admin', 'partner', 'business'], $this->switchablePanelIds());
    }

    public function test_switcher_is_hidden_for_a_single_role_user(): void
    {
        $partner = Partner::factory()->create();
        $this->actingAs($partner->user);
        Filament::setCurrentPanel('partner');

        // Only one accessible panel → nothing to switch to → the widget hides.
        $this->assertSame(['partner'], $this->switchablePanelIds());
        $this->assertFalse(PanelSwitch::make()->isVisible());
    }

    /**
     * @return array<int, string>
     */
    private function switchablePanelIds(): array
    {
        return array_keys(PanelSwitch::make()->getPanels());
    }

    /**
     * @return array{0: User}
     */
    private function dualRole(): array
    {
        $mgr = Partner::factory()->create();
        $user = User::factory()->create(['role' => UserRole::Partner, 'status' => UserStatus::Active]);

        Partner::factory()->create([
            'user_id' => $user->id,
            'parent_partner_id' => $mgr->id,
            'status' => RecordStatus::Active,
        ]);
        BusinessOwner::factory()->create([
            'user_id' => $user->id,
            'partner_id' => $mgr->id,
            'status' => RecordStatus::Active,
        ]);

        return [$user->fresh()];
    }
}

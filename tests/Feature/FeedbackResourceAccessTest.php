<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\FeedbackSubmissions\FeedbackSubmissionResource;
use App\Filament\Resources\FeedbackSubmissions\Pages\ListFeedbackSubmissions;
use App\Models\FeedbackSubmission;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FeedbackResourceAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function makeAdminUser(): User
    {
        return User::factory()->create([
            'role' => UserRole::PiosOwner,
            'status' => UserStatus::Active,
        ]);
    }

    private function makePartnerUser(): User
    {
        return User::factory()
            ->has(Partner::factory(), 'partner')
            ->create([
                'role' => UserRole::Partner,
                'status' => UserStatus::Active,
            ]);
    }

    public function test_admin_can_see_all_feedback(): void
    {
        $admin = $this->makeAdminUser();

        $partnerUser = $this->makePartnerUser();
        $partner = $partnerUser->partner;

        $partnerFeedback = FeedbackSubmission::factory()->forPartner($partner)->create();
        $ownerFeedback = FeedbackSubmission::factory()->assignedToOwner()->create();

        $this->actingAs($admin);

        Livewire::test(ListFeedbackSubmissions::class)
            ->assertCanSeeTableRecords([$partnerFeedback, $ownerFeedback]);
    }

    public function test_partner_can_only_see_their_own_feedback(): void
    {
        $partnerUser = $this->makePartnerUser();
        $partner = $partnerUser->partner;

        $anotherPartnerUser = $this->makePartnerUser();
        $anotherPartner = $anotherPartnerUser->partner;

        $myFeedback = FeedbackSubmission::factory()->forPartner($partner)->create();
        $otherFeedback = FeedbackSubmission::factory()->forPartner($anotherPartner)->create();
        $ownerFeedback = FeedbackSubmission::factory()->assignedToOwner()->create();

        $this->actingAs($partnerUser);

        Livewire::test(ListFeedbackSubmissions::class)
            ->assertCanSeeTableRecords([$myFeedback])
            ->assertCanNotSeeTableRecords([$otherFeedback, $ownerFeedback]);
    }

    public function test_partner_cannot_see_feedback_assigned_to_owner(): void
    {
        $partnerUser = $this->makePartnerUser();

        $ownerFeedback = FeedbackSubmission::factory()->assignedToOwner()->create();

        $this->actingAs($partnerUser);

        Livewire::test(ListFeedbackSubmissions::class)
            ->assertCanNotSeeTableRecords([$ownerFeedback]);
    }

    public function test_resource_has_no_create_button(): void
    {
        $admin = $this->makeAdminUser();
        $this->actingAs($admin);

        Livewire::test(ListFeedbackSubmissions::class)
            ->assertDontSee('New Feedback');

        $this->assertFalse(FeedbackSubmissionResource::canCreate());
    }
}

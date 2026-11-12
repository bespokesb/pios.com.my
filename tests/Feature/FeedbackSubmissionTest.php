<?php

namespace Tests\Feature;

use App\Enums\CustomerCategory;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Customer;
use App\Models\FeedbackSubmission;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_login_page_displays_feedback_link(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Feedback');
        $response->assertSee(route('feedback.show'));
    }

    public function test_guest_can_view_feedback_form_and_slide(): void
    {
        $this->get(route('feedback.show'))
            ->assertOk()
            ->assertSee('Questionnaire &amp; Feedback', false)
            ->assertSee('Full Name')
            ->assertSee('HP No')
            ->assertSee('Partner ID')
            ->assertSee('Slide')
            ->assertSee('Submit');

        $this->get(route('feedback.slide'))
            ->assertOk()
            ->assertSee('PIOS BizGrowth Engine (BGE)');
    }

    public function test_feedback_with_valid_partner_id_creates_prospect_under_that_partner(): void
    {
        $partnerUser = User::factory()->create([
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);
        $partner = Partner::factory()->create([
            'user_id' => $partnerUser->id,
            'partner_no' => 'PTN000123',
        ]);

        $response = $this->post(route('feedback.store'), [
            'full_name' => 'Ahmad Prospek',
            'hp_no' => '0123456789',
            'partner_id_input' => 'ptn000123',
            'q1_income_growth' => 'yes',
            'q2_seeking_way' => 'yes',
            'q3_interested_tools' => 'no',
            'remarks' => 'Berminat untuk tahu lebih lanjut.',
        ]);

        $response->assertRedirect(route('feedback.show'));
        $response->assertSessionHas('success');

        $customer = Customer::where('name', 'Ahmad Prospek')->first();
        $this->assertNotNull($customer);
        $this->assertSame($partner->id, $customer->partner_id);
        $this->assertNull($customer->business_owner_id);
        $this->assertSame(CustomerCategory::Prospect, $customer->category);

        $this->assertDatabaseHas('feedback_submissions', [
            'customer_id' => $customer->id,
            'partner_id' => $partner->id,
            'full_name' => 'Ahmad Prospek',
            'q1_income_growth' => true,
            'q2_seeking_way' => true,
            'q3_interested_tools' => false,
            'assigned_to_owner' => false,
        ]);

        // Visible to the owning partner, invisible to another partner
        $otherPartnerUser = User::factory()->create([
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);
        Partner::factory()->create([
            'user_id' => $otherPartnerUser->id,
            'partner_no' => 'PTN000999',
        ]);

        $this->assertTrue(Customer::visibleTo($partnerUser)->where('id', $customer->id)->exists());
        $this->assertFalse(Customer::visibleTo($otherPartnerUser)->where('id', $customer->id)->exists());
    }

    public function test_feedback_with_blank_partner_id_assigns_prospect_to_owner(): void
    {
        $response = $this->post(route('feedback.store'), [
            'full_name' => 'Siti Tanpa Partner',
            'hp_no' => '0198887766',
            'partner_id_input' => '',
            'q1_income_growth' => 'yes',
            'q2_seeking_way' => 'no',
            'q3_interested_tools' => 'yes',
            'remarks' => '',
        ]);

        $response->assertRedirect(route('feedback.show'));

        $customer = Customer::where('name', 'Siti Tanpa Partner')->first();
        $this->assertNotNull($customer);
        $this->assertNull($customer->partner_id);
        $this->assertNull($customer->business_owner_id);
        $this->assertSame(CustomerCategory::Prospect, $customer->category);

        $submission = FeedbackSubmission::where('customer_id', $customer->id)->first();
        $this->assertNotNull($submission);
        $this->assertNull($submission->partner_id);
        $this->assertTrue($submission->assigned_to_owner);
    }

    public function test_feedback_with_salesperson_name_instead_of_partner_id_assigns_prospect_to_owner(): void
    {
        $partnerUser = User::factory()->create([
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);
        Partner::factory()->create([
            'user_id' => $partnerUser->id,
            'partner_no' => 'PTN000123',
        ]);

        $ownerUser = User::factory()->create([
            'role' => UserRole::PiosOwner,
            'status' => UserStatus::Active,
        ]);

        $response = $this->post(route('feedback.store'), [
            'full_name' => 'Zul Prospek',
            'hp_no' => '01122334455',
            'partner_id_input' => 'Ali b Bakar',
            'q1_income_growth' => 'yes',
            'q2_seeking_way' => 'yes',
            'q3_interested_tools' => 'yes',
            'remarks' => 'Jumpa di roadshow.',
        ]);

        $response->assertRedirect(route('feedback.show'));

        $customer = Customer::where('name', 'Zul Prospek')->first();
        $this->assertNotNull($customer);
        $this->assertNull($customer->partner_id);
        $this->assertNull($customer->business_owner_id);
        $this->assertSame(CustomerCategory::Prospect, $customer->category);

        $this->assertDatabaseHas('feedback_submissions', [
            'customer_id' => $customer->id,
            'partner_id' => null,
            'partner_id_input' => 'Ali b Bakar',
            'assigned_to_owner' => true,
        ]);

        // Visible to PIOS Owner, NOT visible to arbitrary partner
        $this->assertTrue(Customer::visibleTo($ownerUser)->where('id', $customer->id)->exists());
        $this->assertFalse(Customer::visibleTo($partnerUser)->where('id', $customer->id)->exists());
    }

    public function test_feedback_validation_requires_name_hp_no_and_three_questions(): void
    {
        $response = $this->post(route('feedback.store'), []);

        $response->assertSessionHasErrors([
            'full_name',
            'hp_no',
            'q1_income_growth',
            'q2_seeking_way',
            'q3_interested_tools',
        ]);
    }
}

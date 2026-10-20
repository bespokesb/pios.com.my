<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Widgets\FollowUpsDueWidget;
use App\Models\AuditLog;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\CustomerFollowUp;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FollowUpsDueWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_widget_shows_only_the_business_owners_own_due_follow_ups(): void
    {
        [$boUserA, $boA] = $this->businessOwner('P000901', 'BO000901');
        [, $boB] = $this->businessOwner('P000902', 'BO000902');

        // BO A: one overdue (visible), one completed (hidden), one due-in-future (hidden).
        $custA = $this->customer($boA, 'C000901');
        $dueNow = $this->followUp($boA, $custA, now()->subDay()->toDateString());
        $this->followUp($boA, $custA, now()->subDay()->toDateString(), completed: true);
        $this->followUp($boA, $custA, now()->addWeek()->toDateString());

        // BO B: an overdue follow-up that must NOT leak into BO A's widget.
        $custB = $this->customer($boB, 'C000902');
        $foreign = $this->followUp($boB, $custB, now()->subDay()->toDateString());

        $this->actingAs($boUserA);

        Livewire::test(FollowUpsDueWidget::class)
            ->assertCanSeeTableRecords([$dueNow])
            ->assertCanNotSeeTableRecords([$foreign]);
    }

    public function test_widget_is_hidden_for_non_business_owners(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SysAdmin, 'status' => UserStatus::Active]);
        $this->actingAs($admin);

        $this->assertFalse(FollowUpsDueWidget::canView());
    }

    public function test_one_tap_complete_marks_the_follow_up_done_and_audits(): void
    {
        [$boUser, $bo] = $this->businessOwner('P000903', 'BO000903');
        $cust = $this->customer($bo, 'C000903');
        $due = $this->followUp($bo, $cust, now()->subDay()->toDateString());

        $this->actingAs($boUser);

        Livewire::test(FollowUpsDueWidget::class)
            ->callTableAction('complete', $due);

        $this->assertNotNull($due->fresh()->completed_at);
        $this->assertDatabaseHas(AuditLog::class, [
            'auditable_type' => CustomerFollowUp::class,
            'auditable_id' => $due->id,
            'event' => 'updated',
        ]);
    }

    public function test_reschedule_moves_the_due_date(): void
    {
        [$boUser, $bo] = $this->businessOwner('P000904', 'BO000904');
        $cust = $this->customer($bo, 'C000904');
        $due = $this->followUp($bo, $cust, now()->subDay()->toDateString());

        $this->actingAs($boUser);

        $newDate = today()->addDays(3);

        Livewire::test(FollowUpsDueWidget::class)
            ->callTableAction('reschedule', $due, data: ['due_on' => $newDate->toDateString()]);

        $this->assertTrue($due->fresh()->due_on->isSameDay($newDate));
        $this->assertNull($due->fresh()->completed_at);
    }

    /** @return array{0: User, 1: BusinessOwner} */
    private function businessOwner(string $partnerNo, string $boNo): array
    {
        $user = User::factory()->create(['role' => UserRole::BusinessOwner, 'status' => UserStatus::Active]);
        $partner = Partner::query()->create(['partner_no' => $partnerNo, 'applicant_name' => 'P '.$partnerNo]);
        $bo = BusinessOwner::query()->create([
            'user_id' => $user->id,
            'partner_id' => $partner->id,
            'business_owner_no' => $boNo,
            'applicant_name' => 'BO '.$boNo,
        ]);

        return [$user->fresh(), $bo];
    }

    private function customer(BusinessOwner $bo, string $customerNo): Customer
    {
        return Customer::query()->create([
            'business_owner_id' => $bo->id,
            'partner_id' => $bo->partner_id,
            'customer_no' => $customerNo,
            'name' => 'Cust '.$customerNo,
        ]);
    }

    private function followUp(BusinessOwner $bo, Customer $customer, string $dueOn, bool $completed = false): CustomerFollowUp
    {
        return CustomerFollowUp::query()->create([
            'customer_id' => $customer->id,
            'business_owner_id' => $bo->id,
            'note' => 'Call back',
            'due_on' => $dueOn,
            'completed_at' => $completed ? now() : null,
        ]);
    }
}

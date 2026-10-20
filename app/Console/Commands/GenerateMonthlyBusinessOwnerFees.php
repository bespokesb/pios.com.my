<?php

namespace App\Console\Commands;

use App\Enums\FeeStatus;
use App\Enums\FeeType;
use App\Enums\RecordStatus;
use App\Models\BusinessOwner;
use App\Models\Fee;
use App\Models\FeeSetting;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateMonthlyBusinessOwnerFees extends Command
{
    protected $signature = 'fees:generate-monthly {--period= : Billing period YYYY-MM (defaults to current Asia/Kuala_Lumpur month)}';

    protected $description = 'Generate the recurring monthly fee for each active Business Owner (idempotent).';

    public function handle(AuditLogger $audit): int
    {
        $period = $this->option('period') ?: now()->format('Y-m');
        // '!Y-m' resets the unspecified day-of-month to 1, so a backfill like
        // --period=2026-02 run on the 31st can't overflow into the next month
        // before endOfMonth() (2026-02 → 2026-02-28, not 2026-03-31).
        $dueDate = Carbon::createFromFormat('!Y-m', $period)->endOfMonth()->toDateString();
        $amount = FeeSetting::activeAmount(FeeType::BusinessOwnerMonthly);

        if ($amount === null) {
            $this->warn('No active BusinessOwnerMonthly FeeSetting; skipping generation.');

            return self::FAILURE;
        }

        $system = User::system();
        $created = 0;

        BusinessOwner::query()
            ->where('status', RecordStatus::Active)
            ->chunkById(200, function ($owners) use ($period, $dueDate, $amount, $audit, $system, &$created) {
                foreach ($owners as $bo) {
                    $fee = Fee::firstOrCreate(
                        [
                            'business_owner_id' => $bo->id,
                            'fee_type' => FeeType::BusinessOwnerMonthly,
                            'billing_period' => $period,
                        ],
                        [
                            'partner_id' => $bo->partner_id,
                            'amount' => $amount,
                            'due_date' => $dueDate,
                            'status' => FeeStatus::Unpaid,
                        ],
                    );

                    if ($fee->wasRecentlyCreated) {
                        $created++;
                        $audit->log(
                            actor: $system,
                            event: 'fee_generated',
                            auditable: $fee,
                            newValues: $fee->getAttributes(),
                        );
                    }
                }
            });

        $this->info("Generated {$created} monthly fee(s) for period {$period}.");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Enums\TransactionType;
use App\Repositories\Contracts\GoalRepository;
use App\Repositories\Contracts\TransactionRepository;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('savings:demo {--fresh : Remove every existing goal and transaction first}')]
#[Description('Fill the JSON storage with sample goals and saving transactions')]
class SeedDemoData extends Command
{
    public function handle(GoalRepository $goals, TransactionRepository $transactions): int
    {
        if ($this->option('fresh')) {
            foreach ($goals->all() as $goal) {
                $transactions->deleteForGoal($goal['id']);
                $goals->delete($goal['id']);
            }
        }

        $today = Carbon::today();

        $samples = [
            ['name' => 'Emergency Fund', 'description' => 'Three months of living expenses.', 'target_amount' => 3000, 'months' => 8, 'monthly' => 400],
            ['name' => 'New Laptop', 'description' => 'For work and study.', 'target_amount' => 1200, 'months' => 6, 'monthly' => 220],
            ['name' => 'Trip to Japan', 'description' => 'Flights, hotel and food for 10 days.', 'target_amount' => 2500, 'months' => 4, 'monthly' => 300],
            ['name' => 'Motorbike', 'description' => null, 'target_amount' => 1800, 'months' => 2, 'monthly' => 150],
        ];

        foreach ($samples as $sample) {
            $goal = $goals->create([
                'name' => $sample['name'],
                'description' => $sample['description'],
                'target_amount' => (float) $sample['target_amount'],
                'target_date' => $today->copy()->addMonths(random_int(3, 12))->toDateString(),
            ]);

            for ($monthsAgo = $sample['months'] - 1; $monthsAgo >= 0; $monthsAgo--) {
                $date = $today->copy()->subMonthsNoOverflow($monthsAgo)->day(min(random_int(1, 28), $today->day));

                $transactions->create([
                    'goal_id' => $goal['id'],
                    'type' => TransactionType::Deposit->value,
                    'amount' => (float) ($sample['monthly'] + random_int(-5, 5) * 10),
                    'date' => $date->toDateString(),
                    'note' => 'Monthly saving',
                ]);
            }
        }

        $this->components->info('Demo goals and transactions created in '.config('savings.storage_path'));

        return self::SUCCESS;
    }
}

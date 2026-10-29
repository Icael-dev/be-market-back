<?php

namespace App\Console\Commands;

use App\Models\LoyaltyPoints;
use App\Models\LoyaltyTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireLoyaltyPoints extends Command
{
    protected $signature = 'loyalty:expire 
                            {--dry-run : Show what would be expired without making changes}
                            {--months=6 : Number of months after which points expire}';

    protected $description = 'Expire loyalty points that have reached their expiration date';

    public function handle(): int
    {
        $months = (int) $this->option('months');
        $dryRun = $this->option('dry-run');

        $now = now();
        $expiredPoints = LoyaltyPoints::whereNotNull('points_expire_at')
            ->where('points_expire_at', '<=', $now)
            ->where('points', '>', 0)
            ->get();

        if ($expiredPoints->isEmpty()) {
            $this->info('No expired points found.');

            return 0;
        }

        $totalExpired = 0;

        foreach ($expiredPoints as $loyalty) {
            $pointsToExpire = $loyalty->points;

            if ($dryRun) {
                $this->line("[DRY RUN] Would expire {$pointsToExpire} points for user {$loyalty->user_id}");
                $totalExpired += $pointsToExpire;

                continue;
            }

            DB::transaction(function () use ($loyalty, $pointsToExpire) {
                $loyalty->points = 0;
                $loyalty->save();

                LoyaltyTransaction::create([
                    'user_id' => $loyalty->user_id,
                    'type' => 'expired',
                    'points' => -$pointsToExpire,
                    'description' => "Expiration de {$pointsToExpire} points après ".now()->subMonths(6)->format('Y-m-d'),
                ]);
            });

            $this->line("Expired {$pointsToExpire} points for user {$loyalty->user_id}");
            $totalExpired += $pointsToExpire;
        }

        if ($dryRun) {
            $this->info("DRY RUN: Would expire {$totalExpired} points total.");
        } else {
            $this->info("Successfully expired {$totalExpired} points total.");
        }

        return 0;
    }
}

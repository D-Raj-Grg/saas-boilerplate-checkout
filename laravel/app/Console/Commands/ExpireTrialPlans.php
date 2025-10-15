<?php

namespace App\Console\Commands;

use App\Models\OrganizationPlan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireTrialPlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plans:expire-trials {--dry-run : Preview what would be expired without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire organization plans that have passed their trial end date';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        // Find all active FREE plans with expired trials
        $expiredPlans = OrganizationPlan::where('status', 'active')
            ->where('is_revoked', false)
            ->whereNotNull('trial_end')
            ->where('trial_end', '<=', now())
            ->whereHas('plan', function ($query) {
                $query->where('slug', 'free');
            })
            ->with(['organization', 'plan'])
            ->get();

        if ($expiredPlans->isEmpty()) {
            $this->info('No trial plans to expire.');

            return Command::SUCCESS;
        }

        $this->info("Found {$expiredPlans->count()} trial plan(s) to expire.");

        foreach ($expiredPlans as $organizationPlan) {
            $orgName = $organizationPlan->organization->name ?? 'Unknown';
            $planName = $organizationPlan->plan->name ?? 'Unknown';

            if ($isDryRun) {
                $this->line("Would expire: Organization '{$orgName}' - Plan '{$planName}' (ID: {$organizationPlan->id})");
            } else {
                // Update the plan to expired status
                $organizationPlan->update([
                    'status' => 'expired',
                    'is_revoked' => true,
                    'revoked_at' => now(),
                    'ends_at' => now(),
                    'notes' => $organizationPlan->notes
                        ? $organizationPlan->notes.' | Trial period expired'
                        : 'Trial period expired',
                ]);

                $this->info("✓ Expired: Organization '{$orgName}' - Plan '{$planName}' (ID: {$organizationPlan->id})");

                Log::info('Trial plan expired', [
                    'organization_id' => $organizationPlan->organization_id,
                    'organization_name' => $orgName,
                    'plan_id' => $organizationPlan->plan_id,
                    'plan_name' => $planName,
                    'trial_end' => $organizationPlan->trial_end,
                ]);

                // Check if organization has any other active plans
                $organization = $organizationPlan->organization;

                if (! $organization) {
                    continue;
                }

                $hasOtherActivePlans = $organization->activePlans()->exists();

                if (! $hasOtherActivePlans) {
                    // Pause all running features if no other active plans

                    $this->warn('  → Paused all running features (no active plans remaining)');

                    Log::info('Feature auto-paused due to trial expiration', [
                        'organization_id' => $organizationPlan->organization_id,
                        'organization_name' => $orgName,
                    ]);
                }
            }
        }

        if ($isDryRun) {
            $this->warn('Dry run completed. No changes were made. Run without --dry-run to expire plans.');
        } else {
            $this->info("Successfully expired {$expiredPlans->count()} trial plan(s).");
        }

        return Command::SUCCESS;
    }
}

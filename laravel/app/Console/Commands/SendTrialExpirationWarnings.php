<?php

namespace App\Console\Commands;

use App\Jobs\SendTrialExpirationWarningJob;
use App\Models\OrganizationPlan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendTrialExpirationWarnings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plans:send-trial-warnings {--days=2 : Days before expiration to send warning} {--dry-run : Preview what would be sent without sending emails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send warning emails to organizations whose trial is expiring soon';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $daysBeforeExpiration = (int) $this->option('days');
        $isDryRun = $this->option('dry-run');

        // Calculate the target date (e.g., 2 days from now)
        $targetDate = now()->addDays($daysBeforeExpiration);

        // Find active FREE plan trials that will expire on the target date
        // Using whereBetween to catch trials expiring within a 24-hour window of the target
        $expiringPlans = OrganizationPlan::where('status', 'active')
            ->where('is_revoked', false)
            ->whereNotNull('trial_end')
            ->whereBetween('trial_end', [
                $targetDate->startOfDay(),
                $targetDate->endOfDay(),
            ])
            ->whereHas('plan', function ($query) {
                $query->where('slug', 'free');
            })
            ->with(['organization.owner', 'plan'])
            ->get();

        if ($expiringPlans->isEmpty()) {
            $this->info("No trials expiring in {$daysBeforeExpiration} days.");

            return Command::SUCCESS;
        }

        $this->info("Found {$expiringPlans->count()} trial(s) expiring in {$daysBeforeExpiration} days.");

        foreach ($expiringPlans as $organizationPlan) {
            $organization = $organizationPlan->organization;

            if (! $organization) {
                $this->warn('⚠ Skipped: Organization not found');

                continue;
            }

            $owner = $organization->owner;
            $planName = $organizationPlan->plan->name ?? 'Unknown';

            if (! $owner) {
                $this->warn("⚠ Skipped: Organization '{$organization->name}' has no owner");
                Log::warning('Trial warning skipped - no owner', [
                    'organization_id' => $organization->id,
                    'organization_name' => $organization->name,
                ]);

                continue;
            }

            $daysRemaining = (int) now()->diffInDays($organizationPlan->trial_end, false);

            if ($isDryRun) {
                $this->line("Would send email to: {$owner->email} ({$organization->name}) - {$daysRemaining} days remaining");
            } else {
                // Dispatch job to send email
                SendTrialExpirationWarningJob::dispatch($owner, $organization, $daysRemaining);

                $this->info("✓ Queued warning email for: {$owner->email} ({$organization->name}) - {$daysRemaining} days remaining");

                Log::info('Trial expiration warning queued', [
                    'organization_id' => $organization->id,
                    'organization_name' => $organization->name,
                    'owner_email' => $owner->email,
                    'days_remaining' => $daysRemaining,
                    'trial_end' => $organizationPlan->trial_end,
                ]);
            }
        }

        if ($isDryRun) {
            $this->warn('Dry run completed. No emails were sent. Run without --dry-run to queue emails.');
        } else {
            $this->info("Successfully queued {$expiringPlans->count()} trial warning email(s).");
        }

        return Command::SUCCESS;
    }
}

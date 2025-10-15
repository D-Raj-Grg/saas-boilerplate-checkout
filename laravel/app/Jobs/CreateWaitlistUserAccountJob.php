<?php

namespace App\Jobs;

use App\Mail\WaitlistAccountCreatedMail;
use App\Models\Plan;
use App\Models\User;
use App\Models\Waitlist;
use App\Services\OrganizationService;
use App\Services\WorkspaceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreateWaitlistUserAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Waitlist $waitlist
    ) {
        // Delay is now set when dispatching the job
    }

    public function handle(): void
    {
        // Check if account already created
        if ($this->waitlist->converted_at) {
            Log::info('Waitlist account already created', ['waitlist_id' => $this->waitlist->id]);

            return;
        }

        $randomPassword = Str::random(12);

        try {
            DB::transaction(function () use ($randomPassword) {
                // Create user
                $user = User::create([
                    'name' => 'Hello There',
                    'first_name' => 'Hello',
                    'last_name' => 'There',
                    'email' => $this->waitlist->email,
                    'password' => Hash::make($randomPassword),
                ]);

                // Auto-verify email (set after creation since it's not fillable)
                $user->email_verified_at = now();
                $user->save();

                // Create default organization and workspace
                $defaultPlanId = Plan::whereSlug(config('constants.default_org_plan_slug'))->value('id') ?? null;

                $organizationService = app(OrganizationService::class);
                $workspaceService = app(WorkspaceService::class);

                $organization = $organizationService->create($user, [
                    'name' => "Hello There's Organization",
                    'workspace_name' => "Hello There's Workspace",
                    'plan_id' => $defaultPlanId,
                ]);

                $workspaceService->create($organization, $user, [
                    'name' => "Hello There's Workspace",
                ]);

                // Mark waitlist entry as converted
                $this->waitlist->markAsConverted();

                // Send ONLY the waitlist welcome email with credentials
                // No verification email needed since we auto-verified
                Mail::to($this->waitlist->email)->send(
                    new WaitlistAccountCreatedMail($this->waitlist, $randomPassword)
                );

                Log::info('Waitlist user account created with auto-verified email', [
                    'waitlist_id' => $this->waitlist->id,
                    'user_id' => $user->id,
                    'email' => $this->waitlist->email,
                    'email_verified' => true,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Exception creating waitlist user account', [
                'waitlist_id' => $this->waitlist->id,
                'error' => $e->getMessage(),
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }
}

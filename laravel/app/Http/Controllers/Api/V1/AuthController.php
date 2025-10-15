<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateUserProfileRequest;
use App\Jobs\SendEmailVerificationJob;
use App\Jobs\SendWelcomeEmailJob;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;
use App\Services\InvitationService;
use App\Services\OrganizationService;
use App\Services\VerificationService;
use App\Services\WorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseApiController
{
    private OrganizationService $organizationService;

    private InvitationService $invitationService;

    private WorkspaceService $workspaceService;

    private VerificationService $verificationService;

    public function __construct(
        OrganizationService $organizationService,
        InvitationService $invitationService,
        WorkspaceService $workspaceService,
        VerificationService $verificationService
    ) {
        $this->organizationService = $organizationService;
        $this->invitationService = $invitationService;
        $this->workspaceService = $workspaceService;
        $this->verificationService = $verificationService;
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            // Create full name from first_name and last_name if name is not provided
            $name = $request->name ?? trim($request->first_name.' '.$request->last_name);

            $user = User::create([
                'name' => $name,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // $user->email_verified_at = now();
            // $user->save();

            // Check if there's an invitation token
            $invitationAccepted = false;

            if ($request->has('invitation_token')) {
                $invitation = Invitation::where('token', $request->invitation_token)
                    ->where('email', $request->email)
                    ->where('status', 'pending')
                    ->where('expires_at', '>', now())
                    ->first();

                if ($invitation) {
                    try {
                        // Accept the invitation
                        $this->invitationService->acceptInvitation($request->invitation_token, $user);
                        $invitationAccepted = true;
                        $invitedOrganization = $invitation->organization;
                    } catch (\Exception $e) {
                        // Log the error but continue with registration
                        \Log::error('Failed to accept invitation during registration', [
                            'user_id' => $user->id,
                            'invitation_token' => $request->invitation_token,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            // Only create default organization if user wasn't invited to one

            if (! $invitationAccepted) {

                $defaultPlanId = Plan::whereSlug(config('constants.default_org_plan_slug'))->value('id') ?? null;

                /** @var \App\Models\User $user */
                $organization = $this->organizationService->create($user, [
                    'name' => $name."'s Organization",
                    'workspace_name' => $name."'s Workspace",
                    'plan_id' => $defaultPlanId,
                ]);

                $this->workspaceService->create($organization, $user, [
                    'name' => $name."'s Workspace",
                ]);
            }

            // Create email verification token with 48-hour expiry
            $verification = $this->verificationService->createEmailVerification($user, 48 * 60);

            // Send verification email
            SendEmailVerificationJob::dispatch($user, $verification);

            // Send welcome email
            SendWelcomeEmailJob::dispatch($user);

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->createdResponse([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'email_verified' => false,
            ], 'User registered successfully. Please check your email to verify your account.');
        });
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (! auth()->attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['User not found.'],
            ]);
        }
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'email_verified' => (bool) $user->email_verified_at,
            'email_verified_at' => $user->email_verified_at,
        ], 'Login successful');
    }

    // Function not used
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user) {
            $user->currentAccessToken()->delete();
        }

        return $this->successResponse(null, 'Logout successful');
    }

    public function user(Request $request): JsonResponse
    {
        return $this->successResponse($request->user());
    }

    public function me(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // If user has no current context, try to set default
        if (! $user->current_organization_id) {
            $firstOrganization = $user->accessibleOrganizations()->first();
            if ($firstOrganization) {
                $user->current_organization_id = $firstOrganization->id;

                // Find first accessible workspace in this organization
                // For org admins/owners, they can access any workspace in their organization
                $firstWorkspace = null;

                if ($user->isOrganizationAdmin($firstOrganization)) {
                    // Org admin/owner can access any workspace in the organization
                    $firstWorkspace = $firstOrganization->workspaces()->first();
                } else {
                    // Regular members only get workspaces they're directly assigned to
                    $firstWorkspace = $firstOrganization->workspaces()
                        ->whereHas('users', function ($query) use ($user): void {
                            $query->where('user_id', $user->id);
                        })
                        ->first();
                }

                if ($firstWorkspace) {
                    $user->current_workspace_id = $firstWorkspace->id;
                }

                $user->save();
            }
        }

        // Get accessible organizations with minimal data including current plan
        $organizations = $user->accessibleOrganizations()
            ->select('organizations.id', 'organizations.uuid', 'organizations.name', 'organizations.slug', 'organizations.owner_id')
            ->get()
            ->map(function ($org) use ($user) {
                /** @var \App\Models\Organization $org */
                $currentPlan = $org->getCurrentPlan();
                $planStatus = 'active';

                // If no active plan, get most recent inactive plan (any status except 'active') for display
                if (! $currentPlan) {
                    $inactivePlan = $org->organizationPlans()
                        ->where('status', '!=', 'active')
                        ->with('plan')
                        ->orderBy('updated_at', 'desc')
                        ->first();

                    if ($inactivePlan && $inactivePlan->plan) {
                        $currentPlan = $inactivePlan->plan;
                        $planStatus = 'inactive';
                    } else {
                        $planStatus = 'none';
                    }
                }

                return [
                    'uuid' => $org->uuid,
                    'name' => $org->name,
                    'slug' => $org->slug,
                    'is_owner' => $user->isOrganizationOwner($org),
                    'current_plan' => [
                        'name' => $currentPlan ? $currentPlan->name : 'No Plan',
                        'slug' => $currentPlan ? $currentPlan->slug : 'none',
                        'status' => $planStatus, // 'active' | 'inactive' | 'none'
                    ],
                ];
            });

        // Get user's accessible workspaces with roles
        $accessibleWorkspaces = $user->accessibleWorkspaces();

        /** @var \Illuminate\Support\Collection<int, array{uuid: string, name: string, slug: string, organization_uuid: string, role: string}> $workspaces */
        $workspaces = $accessibleWorkspaces->map(function (array $workspaceData): array {
            /** @var \App\Models\Workspace $workspace */
            $workspace = $workspaceData['workspace'];
            /** @var string $role */
            $role = $workspaceData['role'];
            /** @var \App\Models\Organization $organization */
            $organization = $workspace->organization;

            return [
                'uuid' => $workspace->uuid,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
                'organization_uuid' => $organization->uuid,
                'role' => $role,
            ];
        });

        // Get current organization and workspace UUIDs
        $currentOrganizationUuid = null;
        $currentWorkspaceUuid = null;

        if ($user->current_organization_id) {
            /** @var \App\Models\Organization|null $currentOrg */
            $currentOrg = Organization::find($user->current_organization_id);
            $currentOrganizationUuid = $currentOrg?->uuid;
        }

        if ($user->current_workspace_id) {
            /** @var \App\Models\Workspace|null $currentWs */
            $currentWs = Workspace::find($user->current_workspace_id);
            $currentWorkspaceUuid = $currentWs?->uuid;
        }

        // Get current workspace permissions
        $currentWorkspacePermissions = [];
        if ($user->current_workspace_id) {
            /** @var \App\Models\Workspace|null $currentWorkspace */
            $currentWorkspace = \App\Models\Workspace::find($user->current_workspace_id);
            if ($currentWorkspace) {
                // Organization owners/admins get permissions even if they don't belong to the workspace
                $organization = $currentWorkspace->organization;
                if (($organization instanceof \App\Models\Organization && $user->isOrganizationAdmin($organization)) || $user->belongsToWorkspace($currentWorkspace)) {
                    $currentWorkspacePermissions = $user->getWorkspacePermissions($currentWorkspace);
                }
            }
        }

        // Get current organization permissions
        $currentOrganizationPermissions = [];
        $currentOrganizationPlanLimits = [];
        if ($user->current_organization_id) {
            /** @var \App\Models\Organization|null $currentOrganization */
            $currentOrganization = \App\Models\Organization::find($user->current_organization_id);
            if ($currentOrganization) {
                $currentOrganizationPermissions = $user->getOrganizationPermissions($currentOrganization);

                // Get plan limits and usage for current organization
                // Pass current workspace for workspace-specific features
                $currentWorkspace = $user->current_workspace_id ? Workspace::find($user->current_workspace_id) : null;
                $currentPlan = $currentOrganization->getCurrentPlan();

                // Determine plan status and data (industry standard: always return plan object)
                $planStatus = 'active';
                $hasActivePlan = (bool) $currentPlan;
                $inactivePlanData = null;

                // If no active plan, get most recent inactive plan (any status except 'active') for display
                if (! $currentPlan) {
                    $inactivePlanData = $currentOrganization->organizationPlans()
                        ->where('status', '!=', 'active')
                        ->with('plan')
                        ->orderBy('updated_at', 'desc')
                        ->first();

                    if ($inactivePlanData && $inactivePlanData->plan) {
                        $currentPlan = $inactivePlanData->plan;
                        $planStatus = 'inactive';
                    } else {
                        // No plan at all (edge case)
                        $planStatus = 'none';
                    }
                }

                // Get trial status (optimized - single call instead of 4)
                $trialInfo = $currentOrganization->getTrialInfo();
                $isInTrial = $trialInfo['is_active'];
                $isTrialExpired = $trialInfo['is_expired'];
                $trialDaysRemaining = $trialInfo['days_remaining'];
                $trialEndsAt = $trialInfo['ends_at']?->toIso8601String();

                // If we have an inactive plan with trial dates, show trial as expired
                if ($inactivePlanData && $inactivePlanData->trial_end) {
                    $isInTrial = false;
                    $isTrialExpired = $inactivePlanData->trial_end->isPast();
                    $trialDaysRemaining = (int) now()->diffInDays($inactivePlanData->trial_end, false);
                    $trialEndsAt = $inactivePlanData->trial_end->toIso8601String();
                }

                $currentOrganizationPlanLimits = [
                    'plan' => [
                        'name' => $currentPlan ? $currentPlan->name : 'No Plan',
                        'slug' => $currentPlan ? $currentPlan->slug : 'none',
                        'status' => $planStatus, // 'active' | 'inactive' | 'none'
                    ],
                    'features' => $hasActivePlan ? $currentOrganization->getUsageSummary($currentWorkspace) : [],
                    'trial' => [
                        'is_trial' => $isInTrial,
                        'is_expired' => $isTrialExpired,
                        'days_remaining' => $trialDaysRemaining,
                        'ends_at' => $trialEndsAt,
                    ],
                    'has_active_plan' => $hasActivePlan, // Simple boolean for frontend
                ];
            }
        }

        // No workspace invitations - only organization invitations exist

        return $this->successResponse([
            'user' => [
                'name' => $user->name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'current_organization_uuid' => $currentOrganizationUuid,
                'current_workspace_uuid' => $currentWorkspaceUuid,
            ],
            'organizations' => $organizations,
            'workspaces' => $workspaces,
            'current_workspace_permissions' => $currentWorkspacePermissions,
            'current_organization_permissions' => $currentOrganizationPermissions,
            'current_organization_plan_limits' => $currentOrganizationPlanLimits,
        ]);
    }

    /**
     * Update user profile information.
     */
    public function update(UpdateUserProfileRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $updateData = [];
        $passwordChanged = false;

        // Update name fields if provided
        if ($request->has('first_name')) {
            $updateData['first_name'] = $request->first_name;
        }

        if ($request->has('last_name')) {
            $updateData['last_name'] = $request->last_name;
        }

        // Update full name if first_name or last_name changed
        if (isset($updateData['first_name']) || isset($updateData['last_name'])) {
            $firstName = $updateData['first_name'] ?? $user->first_name;
            $lastName = $updateData['last_name'] ?? $user->last_name;
            $updateData['name'] = trim($firstName.' '.$lastName);
        }

        // Update password if provided
        if ($request->has('new_password')) {
            $updateData['password'] = Hash::make($request->new_password);
            $passwordChanged = true;
        }

        // Update user
        $user->update($updateData);

        // Revoke all tokens if password was changed (force re-login on other devices)
        // Preserve WordPress Connection tokens
        if ($passwordChanged) {
            $currentToken = $user->currentAccessToken();
            $user->tokens()
                ->where('id', '!=', $currentToken->id)
                ->where('name', '!=', 'WordPress Connection')
                ->delete();
        }

        return $this->successResponse([
            'user' => [
                'name' => $user->name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
            ],
            'password_changed' => $passwordChanged,
        ], 'Profile updated successfully'.($passwordChanged ? '. You have been logged out of other devices (WordPress connections preserved).' : ''));
    }
}

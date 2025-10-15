<?php

namespace Tests\Unit\Models;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed plans for the tests
        $this->artisan('db:seed', ['--class' => 'PlanSeeder']);
    }

    public function test_workspace_can_be_deleted_when_multiple_workspaces_exist(): void
    {
        $plan = Plan::first();
        $organization = Organization::factory()->create(['plan_id' => $plan->id]);

        // Create two workspaces in the same organization
        $workspace1 = Workspace::factory()->create(['organization_id' => $organization->id]);
        $workspace2 = Workspace::factory()->create(['organization_id' => $organization->id]);

        // Both workspaces should be deletable since there are multiple
        $this->assertTrue($workspace1->canBeDeleted());
        $this->assertTrue($workspace2->canBeDeleted());
    }

    public function test_workspace_cannot_be_deleted_when_it_is_the_last_workspace(): void
    {
        $plan = Plan::first();
        $organization = Organization::factory()->create(['plan_id' => $plan->id]);

        // Create only one workspace in the organization
        $workspace = Workspace::factory()->create(['organization_id' => $organization->id]);

        // Workspace should not be deletable as it's the last one
        $this->assertFalse($workspace->canBeDeleted());
    }

    public function test_workspace_can_be_deleted_after_another_is_created(): void
    {
        $plan = Plan::first();
        $organization = Organization::factory()->create(['plan_id' => $plan->id]);

        // Start with one workspace (cannot be deleted)
        $workspace1 = Workspace::factory()->create(['organization_id' => $organization->id]);
        $this->assertFalse($workspace1->canBeDeleted());

        // Create a second workspace
        $workspace2 = Workspace::factory()->create(['organization_id' => $organization->id]);

        // Now both can be deleted
        $this->assertTrue($workspace1->canBeDeleted());
        $this->assertTrue($workspace2->canBeDeleted());
    }

    public function test_workspace_cannot_be_deleted_after_other_workspace_is_deleted(): void
    {
        $plan = Plan::first();
        $organization = Organization::factory()->create(['plan_id' => $plan->id]);

        // Create two workspaces
        $workspace1 = Workspace::factory()->create(['organization_id' => $organization->id]);
        $workspace2 = Workspace::factory()->create(['organization_id' => $organization->id]);

        // Both can be deleted initially
        $this->assertTrue($workspace1->canBeDeleted());
        $this->assertTrue($workspace2->canBeDeleted());

        // Delete one workspace
        $workspace2->delete();

        // Refresh workspace1 to get updated organization relationship
        $workspace1->refresh();

        // Now workspace1 cannot be deleted as it's the last one
        $this->assertFalse($workspace1->canBeDeleted());
    }

    public function test_workspaces_from_different_organizations_do_not_affect_each_other(): void
    {
        $plan = Plan::first();

        // Create two organizations
        $organization1 = Organization::factory()->create(['plan_id' => $plan->id]);
        $organization2 = Organization::factory()->create(['plan_id' => $plan->id]);

        // Each organization has one workspace
        $workspace1 = Workspace::factory()->create(['organization_id' => $organization1->id]);
        $workspace2 = Workspace::factory()->create(['organization_id' => $organization2->id]);

        // Neither can be deleted as they are each the only workspace in their organization
        $this->assertFalse($workspace1->canBeDeleted());
        $this->assertFalse($workspace2->canBeDeleted());

        // Create another workspace in organization1
        $workspace3 = Workspace::factory()->create(['organization_id' => $organization1->id]);

        // Now workspace1 and workspace3 can be deleted, but workspace2 still cannot
        $this->assertTrue($workspace1->canBeDeleted());
        $this->assertTrue($workspace3->canBeDeleted());
        $this->assertFalse($workspace2->canBeDeleted());
    }

    public function test_soft_deleted_workspaces_are_not_counted(): void
    {
        $plan = Plan::first();
        $organization = Organization::factory()->create(['plan_id' => $plan->id]);

        // Create three workspaces
        $workspace1 = Workspace::factory()->create(['organization_id' => $organization->id]);
        $workspace2 = Workspace::factory()->create(['organization_id' => $organization->id]);
        $workspace3 = Workspace::factory()->create(['organization_id' => $organization->id]);

        // All can be deleted initially
        $this->assertTrue($workspace1->canBeDeleted());
        $this->assertTrue($workspace2->canBeDeleted());
        $this->assertTrue($workspace3->canBeDeleted());

        // Soft delete two workspaces
        $workspace2->delete();
        $workspace3->delete();

        // Refresh workspace1 to get updated relationship
        $workspace1->refresh();

        // Only workspace1 remains (soft deleted ones don't count)
        $this->assertFalse($workspace1->canBeDeleted());
    }

    public function test_workspace_without_organization_cannot_be_deleted(): void
    {
        // Create a workspace without properly setting up organization relationship
        $workspace = new Workspace;
        $workspace->name = 'Test Workspace';
        $workspace->organization_id = 999; // Non-existent organization ID

        // When organization relationship returns null, should not be deletable
        $this->assertFalse($workspace->canBeDeleted());
    }
}

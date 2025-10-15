<?php

use App\Enums\WorkspaceRole;

return [
    /*
    |--------------------------------------------------------------------------
    | Workspace Role Permissions
    |--------------------------------------------------------------------------
    |
    | This configuration defines the permissions associated with each workspace
    | role. These permissions are used by the WorkspacePolicy to determine
    | what actions a user can perform within a workspace.
    |
    */

    WorkspaceRole::MANAGER->value => [
        'workspace.update',
        'workspace.manage_settings',
        'workspace.invite_users',
        'workspace.remove_users',
        'workspace.view_audit_logs',
    ],

    WorkspaceRole::EDITOR->value => [
        'workspace.view',
    ],

    WorkspaceRole::VIEWER->value => [
        'workspace.view',
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Hierarchy
    |--------------------------------------------------------------------------
    |
    | Defines the hierarchy levels for each role. Higher numbers indicate
    | higher authority within the workspace.
    |
    */

    'hierarchy' => [
        WorkspaceRole::MANAGER->value => 3,
        WorkspaceRole::EDITOR->value => 2,
        WorkspaceRole::VIEWER->value => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Assignment Permissions
    |--------------------------------------------------------------------------
    |
    | Defines which roles each role can assign to other users.
    |
    */

    'can_assign' => [
        WorkspaceRole::MANAGER->value => [
            WorkspaceRole::EDITOR->value,
            WorkspaceRole::VIEWER->value,
        ],
        WorkspaceRole::EDITOR->value => [],
        WorkspaceRole::VIEWER->value => [],
    ],
];

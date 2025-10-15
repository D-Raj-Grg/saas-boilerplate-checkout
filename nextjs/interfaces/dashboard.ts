// Dashboard interfaces for main dashboard data

export interface DashboardStats {
  total_users: number;
}

export interface DashboardUser {
  id: number;
  name: string;
  first_name: string;
  last_name: string;
  current_organization_id: number;
  current_workspace_id: number;
  email: string;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
  current_organization_uuid: string;
  current_workspace_uuid: string;
  current_organization: {
    uuid: string;
    name: string;
    slug: string;
    description: string | null;
    settings: any[];
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    plan: {
      uuid: string;
      name: string;
      slug: string;
      description: string;
      features: string[];
      limits: {
        workspaces: number;
        members: number;
        monthly_events: number;
        api_rate_multiplier: number;
      };
      price: string;
      billing_cycle: string;
      is_active: boolean;
      created_at: string;
      updated_at: string;
    };
  };
  current_workspace: {
    uuid: string;
    name: string;
    slug: string;
    description: string | null;
    settings: any[];
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
  };
}

export interface DashboardData {
  title: string;
  user: DashboardUser;
  stats: DashboardStats;
  links: {
    api_docs: string;
    api_health: string;
  };
}

export interface DashboardResponse {
  success: boolean;
  message: string;
  data: DashboardData;
}

export interface DashboardActionResult {
  success: boolean;
  data?: DashboardData;
  error?: string;
}
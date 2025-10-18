export interface Organization {
  uuid: string;
  name: string;
  slug: string;
  description: string;
  currency: string;
  market: string;
  total_workspaces: number;
  total_members: number;
  total_connections: number;
  created_at: string;
  updated_at: string;
}

export interface TeamMember {
  uuid: string;
  name: string;
  email: string;
  role: string;
  joined_at: string;
}

export interface OrganizationConnection {
  uuid: string;
  integration_name: string;
  site_url: string;
  status: string;
  last_sync_at: string;
}

export interface Workspace {
  uuid: string;
  name: string;
  slug: string;
  description: string;
  total_members: number;
  total_connections: number;
  created_at: string;
  updated_at: string;
}

export interface Plan {
  uuid: string;
  name: string;
  slug: string;
  features: {
    priority_support: boolean;
    [key: string]: boolean;
  };
  limits: {
    workspaces_per_organization: number;
    users_per_workspace: number;
    api_calls_per_month: number;
    data_retention_days: number;
    [key: string]: number;
  };
  price: string;
  formatted_price: string;
  purchased_at?: string;
  started_at?: string;
  status?: string;
}

export interface OrganizationStats {
  organization: Organization;
  workspaces: Workspace[];
  plan: Plan | null;
  all_plans: Plan[];
}

export interface OrganizationStatsResponse {
  success: true;
  data: OrganizationStats;
}
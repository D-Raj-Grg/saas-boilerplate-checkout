// Connection interfaces for managing third-party integrations

export interface Connection {
  id: string;
  integration_name: string;
  site_url: string;
  status: 'active' | 'inactive' | 'error';
  last_sync_at: string | null;
  created_at: string;
  plugin_version?: string;
}

// API Response interfaces
export interface ConnectionsListResponse {
  success: boolean;
  message: string;
  data: Connection[];
}

export interface ConnectionInitiateResponse {
  success: boolean;
  message: string;
  data?: {
    oauth_token: string;
    redirect_url: string;
    expires_at: string;
  };
}

// Action Result interfaces  
export interface ConnectionsListResult {
  success: boolean;
  message?: string;
  error?: string;
  data?: Connection[];
}

export interface DeleteConnectionResult {
  success: boolean;
  message?: string;
  error?: string;
}

export interface ConnectionInitiateResult {
  success: boolean;
  message?: string;
  error?: string;
  data?: {
    oauth_token: string;
    redirect_url: string;
    expires_at: string;
  };
}

export interface SyncConnectionResponse {
  success: boolean;
  message: string;
  data: {
    connection_id: string;
    site_url: string;
    status: string;
  };
}

export interface SyncConnectionResult {
  success: boolean;
  message?: string;
  error?: string;
  data?: {
    connection_id: string;
    site_url: string;
    status: string;
  };
}
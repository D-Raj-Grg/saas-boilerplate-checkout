
export interface ActionResult<T = any> {
  success: boolean;
  error?: string;
  data?: T;
}

export interface ApiResponse<T = any> {
  success: boolean;
  message?: string;
  error?: string;
  data?: T;
  errors?: Record<string, string[]>;
}

export interface PaginatedResponse<T = any> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}

export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
  status?: number;
}

export interface RequestConfig {
  method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
  headers?: Record<string, string>;
  body?: string;
  cache?: RequestCache;
  next?: { revalidate?: number; tags?: string[] };
}

export interface ApiRequestOptions extends RequestConfig {
  requireAuth?: boolean;
  baseUrl?: string;
}
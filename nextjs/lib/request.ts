import { redirect } from 'next/navigation'
import { cookies } from 'next/headers'

const API_BASE_URL = process.env.NEXT_PUBLIC_API_BASE_URL;
const AUTH_TOKEN_NAME = process.env.AUTH_TOKEN_NAME || 'secure_cookie_name';

export interface RequestOptions {
  headers?: Record<string, string>;
  init?: RequestInit;
  noDefaultHeaders?: boolean;
}

export async function remoteGet<T = unknown>(url: string, headers: Record<string, string> = {}, init: RequestInit = {}): Promise<T> {
  const session = (await cookies()).get(AUTH_TOKEN_NAME)?.value || '';
  const defaultHeaders: Record<string, string> = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };

  if (session) {
    defaultHeaders['Authorization'] = `Bearer ${session}`;
  }

  const fullUrl = url.startsWith('http') ? url : `${API_BASE_URL}${url}`;

  const response = await fetch(fullUrl, {
    ...init,
    method: 'GET',
    mode: 'cors',
    cache: 'default',
    credentials: 'same-origin',
    headers: {
      ...defaultHeaders,
      ...headers,
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });

  return handleResponse<T>(response);
}

export async function remotePost<T = unknown>(
  url: string,
  data: unknown = {},
  headers: Record<string, string> = {},
  noDefaultHeaders: boolean = false
): Promise<T> {
  const session = (await cookies()).get(AUTH_TOKEN_NAME)?.value || '';
  const defaultHeaders: Record<string, string> = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };

  if (session) {
    defaultHeaders['Authorization'] = `Bearer ${session}`;
  }

  if (noDefaultHeaders) {
    delete defaultHeaders['Content-Type'];
    delete defaultHeaders['Accept'];
  }

  const fullUrl = url.startsWith('http') ? url : `${API_BASE_URL}${url}`;

  // Handle FormData separately
  const body = data instanceof FormData
    ? data
    : (typeof data === 'string' ? data : JSON.stringify(data));

  const response = await fetch(fullUrl, {
    method: 'POST',
    mode: 'cors',
    cache: 'no-cache',
    credentials: 'same-origin',
    headers: {
      ...defaultHeaders,
      ...headers,
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
    body,
  });

  return handleResponse<T>(response);
}

export async function remoteDelete<T = unknown>(url: string, headers: Record<string, string> = {}): Promise<T> {
  const session = (await cookies()).get(AUTH_TOKEN_NAME)?.value || '';
  const defaultHeaders: Record<string, string> = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };

  if (session) {
    defaultHeaders['Authorization'] = `Bearer ${session}`;
  }

  const fullUrl = url.startsWith('http') ? url : `${API_BASE_URL}${url}`;

  const response = await fetch(fullUrl, {
    method: 'DELETE',
    mode: 'cors',
    cache: 'no-cache',
    credentials: 'same-origin',
    headers: {
      ...defaultHeaders,
      ...headers,
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });

  return handleResponse<T>(response);
}

export async function remotePut<T = unknown>(url: string, data: unknown = {}, headers: Record<string, string> = {}): Promise<T> {
  const session = (await cookies()).get(AUTH_TOKEN_NAME)?.value || '';
  const defaultHeaders: Record<string, string> = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };

  if (session) {
    defaultHeaders['Authorization'] = `Bearer ${session}`;
  }

  const fullUrl = url.startsWith('http') ? url : `${API_BASE_URL}${url}`;

  const body = data instanceof FormData
    ? data
    : (typeof data === 'string' ? data : JSON.stringify(data));

  const response = await fetch(fullUrl, {
    method: 'PUT',
    mode: 'cors',
    cache: 'no-cache',
    credentials: 'same-origin',
    headers: {
      ...defaultHeaders,
      ...headers,
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
    body,
  });

  return handleResponse<T>(response);
}

export async function remotePatch<T = unknown>(url: string, data: unknown = {}, headers: Record<string, string> = {}): Promise<T> {
  const session = (await cookies()).get(AUTH_TOKEN_NAME)?.value || '';
  const defaultHeaders: Record<string, string> = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };

  if (session) {
    defaultHeaders['Authorization'] = `Bearer ${session}`;
  }

  const fullUrl = url.startsWith('http') ? url : `${API_BASE_URL}${url}`;

  const body = data instanceof FormData
    ? data
    : (typeof data === 'string' ? data : JSON.stringify(data));

  const response = await fetch(fullUrl, {
    method: 'PATCH',
    mode: 'cors',
    cache: 'no-cache',
    credentials: 'same-origin',
    headers: {
      ...defaultHeaders,
      ...headers,
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
    body,
  });

  return handleResponse<T>(response);
}

export async function remoteGetPublic<T = unknown>(url: string, headers: Record<string, string> = {}, init: RequestInit = {}): Promise<T> {
  const defaultHeaders: Record<string, string> = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };

  const fullUrl = url.startsWith('http') ? url : `${API_BASE_URL}${url}`;

  const response = await fetch(fullUrl, {
    ...init,
    method: 'GET',
    mode: 'cors',
    cache: 'default',
    headers: {
      ...defaultHeaders,
      ...headers,
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });

  return handleResponse<T>(response);
}

export async function remotePostPublic<T = unknown>(url: string, data: unknown = {}, headers: Record<string, string> = {}): Promise<T> {
  const defaultHeaders: Record<string, string> = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };

  const fullUrl = url.startsWith('http') ? url : `${API_BASE_URL}${url}`;

  const body = data instanceof FormData
    ? data
    : (typeof data === 'string' ? data : JSON.stringify(data));

  const response = await fetch(fullUrl, {
    method: 'POST',
    mode: 'cors',
    cache: 'no-cache',
    headers: {
      ...defaultHeaders,
      ...headers,
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
    body,
  });

  return handleResponse<T>(response);
}

async function handleResponse<T>(response: Response): Promise<T> {
  'use server'

  let data: unknown = null;
  const contentType = response.headers?.get('content-type');

  if (contentType?.includes('application/json')) {
    try {
      data = await response.json();
    } catch {
    }
  }

  if (!response.ok) {
    // Handle authentication errors
    if (response.status === 401) {
      const error = (data as { message?: string })?.message || response.statusText;
      if (error === 'Unauthenticated.' || error === 'Unauthorized') {
        return redirect('/api/logout');
      }
    }

    // For 404 errors, ensure we have a proper error structure
    if (response.status === 404) {
      data = {
        success: false,
        message: (data as { message?: string })?.message || 'Resource not found',
        status: response.status
      } as T;
    }

    // For other errors, return the data which includes error information
    // This allows the calling server action to handle specific errors
    // If there's no data, create an error response
    if (!data) {
      data = {
        success: false,
        message: response.statusText || 'Request failed',
        status: response.status
      } as T;
    }

    // Ensure data has success: false for error responses
    if (data && typeof data === 'object' && !('success' in data)) {
      (data as Record<string, unknown>).success = false;
    }
  } else {
    // For successful responses, ensure we have a proper success structure
    if (data && typeof data === 'object' && !('success' in data)) {
      // If the response has a 'data' property, it's already in the expected structure
      // We just need to add the success field
      if ('data' in data) {
        (data as Record<string, unknown>).success = true;
      } else {
        // If it's raw data without structure, wrap it properly
        data = {
          success: true,
          data: data,
          message: 'Request successful'
        } as T;
      }
    }
  }

  return data as T;
}
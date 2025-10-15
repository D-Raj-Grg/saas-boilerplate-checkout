// Waitlist related interfaces

export interface WaitlistData {
  first_name?: string;
  last_name?: string;
  email: string;
  metadata?: {
    source?: string;
    utm_source?: string;
    utm_medium?: string;
    utm_campaign?: string;
    utm_content?: string;
    utm_term?: string;
    referrer?: string;
    [key: string]: string | undefined;
  };
}

export interface WaitlistResponse {
  success: boolean;
  message?: string;
  data?: {
    id: number;
    email: string;
    first_name?: string;
    last_name?: string;
    created_at: string;
    metadata?: Record<string, any>;
  };
}
import { User } from "./user";

export interface LoginResponse {
  success: boolean;
  message?: string;
  data?: {
    access_token: string;
    user: User;
  };
}

export interface SignupResponse {
  success: boolean;
  message?: string;
  data?: {
    access_token: string;
    user: User;
  };
}

export interface AuthActionResult {
  success: boolean;
  error?: string;
  message?: string;
  data?: any;
  user?: any; // Legacy - will be replaced by UserData from /me endpoint
}

export interface ForgotPasswordData {
  email: string;
}

export interface VerifyPasswordTokenData {
  token: string;
}

export interface ResetPasswordData {
  token: string;
  password: string;
  password_confirmation: string;
}

export interface LoginData {
  email: string;
  password: string;
}

export interface SignupData {
  firstName: string;
  lastName: string;
  email: string;
  password: string;
  invitationToken?: string;
}

export interface UserProfileResponse {
  success: boolean;
  data: User;
}

export interface AuthError {
  success: false;
  message: string;
  error_code?: string;
  errors?: Record<string, string[]>;
}

export interface ForgotPasswordResponse {
  success: boolean;
  message: string;
  data: {
    expires_in_minutes: number;
  };
}

export interface ResetPasswordResponse {
  success: boolean;
  message: string;
  data: null;
}
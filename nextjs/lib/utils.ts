import { clsx, type ClassValue } from "clsx"
import { twMerge } from "tailwind-merge"
import { ResponseCookie } from "next/dist/compiled/@edge-runtime/cookies"

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

// URL validation regex pattern
// Matches: http(s)://domain.com, localhost, IP addresses, with optional ports and paths
export const URL_REGEX = /^(https?:\/\/)(?:localhost|\d{1,3}(?:\.\d{1,3}){3}|(?:[\w-]+\.)+[\w-]{2,})(?::\d{2,5})?(?:[/?#][^\s"]*)?$/i;

/**
 * Validates if a string is a properly formatted URL
 * @param urlString - The URL string to validate
 * @returns boolean indicating if the URL is valid
 */
export function isValidUrl(urlString: string | undefined | null): boolean {
  if (!urlString) return false;
  return URL_REGEX.test(urlString.trim());
}

/**
 * Get cookie options with automatic environment-based adjustments
 * @param obj - Optional overrides for cookie options
 * @returns Cookie options object with secure defaults
 */
export function getCookieOptions(obj: Partial<ResponseCookie> = {}): Partial<ResponseCookie> {
  const isProduction = process.env.NODE_ENV === "production";

  return {
    httpOnly: true,
    secure: isProduction,
    maxAge: 60 * 60 * 24 * 7, // 7 days
    path: "/",
    sameSite: isProduction ? "none" : "lax",
    ...obj,
  };
}

/**
 * Get status colors for backward compatibility
 * @param status - Status string or object with value property
 * @returns CSS classes string for status styling
 */
export function getStatusColor(status: string | { value: string } | undefined): string {
  const statusValue = typeof status === 'string' ? status : status?.value;

  // Handle connection-specific statuses
  switch (statusValue) {
    case 'active':
      return 'bg-green-100 text-green-800 border-green-200';
    case 'inactive':
      return 'bg-gray-100 text-gray-800 border-gray-200';
    case 'error':
      return 'bg-red-100 text-red-800 border-red-200';
  }
  return '';
}
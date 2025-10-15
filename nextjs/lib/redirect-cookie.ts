import Cookies from 'js-cookie';

const REDIRECT_URL_COOKIE = process.env.AUTH_REDIRECT_URL_NAME || "bsf-redirect-url";

export function setRedirectUrl(url: string) {
  // Validate URL to prevent open redirect vulnerabilities
  try {
    const parsedUrl = new URL(url);
    // Only allow same-origin redirects or relative paths
    if (parsedUrl.origin === window.location.origin || url.startsWith('/')) {
      Cookies.set(REDIRECT_URL_COOKIE, url, {
        expires: 1, // 1 day
        sameSite: 'lax',
        secure: process.env.NODE_ENV === 'production'
      });
    }
  } catch {
    // If URL parsing fails, check if it's a valid relative path
    if (url.startsWith('/')) {
      Cookies.set(REDIRECT_URL_COOKIE, url, {
        expires: 1,
        sameSite: 'lax',
        secure: process.env.NODE_ENV === 'production'
      });
    }
  }
}

export function getRedirectUrl(): string | undefined {
  return Cookies.get(REDIRECT_URL_COOKIE);
}

export function removeRedirectUrl() {
  Cookies.remove(REDIRECT_URL_COOKIE);
}

export function getAndClearRedirectUrl(): string | undefined {
  const url = getRedirectUrl();
  if (url) {
    removeRedirectUrl();
  }
  return url;
}
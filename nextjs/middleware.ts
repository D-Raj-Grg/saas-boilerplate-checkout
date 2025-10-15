import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";

// Authentication token name from environment variable
const AUTH_TOKEN_NAME = process.env.AUTH_TOKEN_NAME || 'secure_cookie_name';

// Define protected routes - all routes that require authentication
const protectedRoutes = ["/dashboard", "/organization", "/organizations", "/workspaces", "/workspace", "/invitations", "/settings", "/support", "/connect", "/connections"];
const authRoutes = ["/login", "/signup", "/pre-launch-register-b7f9d4e8c2a1", "/forgot-password", "/reset-password", "/verify-email", "/join-waitlist", "/pricing"];
const publicRoutes = ["/pricing"];

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;
  const authToken = request.cookies.get(AUTH_TOKEN_NAME);

  // Check if the current route is public
  const isPublicRoute = publicRoutes.some((route) => pathname.startsWith(route));

  // Allow public routes without any checks
  if (isPublicRoute) {
    return NextResponse.next();
  }

  // Check if the current route is protected
  const isProtectedRoute = protectedRoutes.some((route) => pathname.startsWith(route));
  const isAuthRoute = authRoutes.some((route) => pathname.startsWith(route));

  // If accessing a protected route without auth token, redirect to login
  if (isProtectedRoute && !authToken) {
    const loginUrl = new URL("/login", request.url);
    // Include the full path with query parameters
    const fullUrl = pathname + request.nextUrl.search;
    loginUrl.searchParams.set("redirect_url", fullUrl);
    return NextResponse.redirect(loginUrl);
  }

  // If accessing auth routes with a valid token, redirect to dashboard

  if (isAuthRoute && authToken && pathname !== "/verify-email") {
    return NextResponse.redirect(new URL("/dashboard", request.url));
  }

  return NextResponse.next();
}

export const config = {
  matcher: [
    /*
     * Match all request paths except for the ones starting with:
     * - _next/static (static files)
     * - _next/image (image optimization files)
     * - favicon.ico (favicon file)
     * - public folder
     */
    "/((?!_next/static|_next/image|favicon.ico|public).*)",
  ],
};
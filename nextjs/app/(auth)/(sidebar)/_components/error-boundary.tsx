"use client";

import React, { Component, ErrorInfo, ReactNode } from "react";
import { Button } from "@/components/ui/button";
import { AlertCircle, RefreshCw } from "lucide-react";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";

interface ErrorFallbackProps {
  error: Error;
  resetErrorBoundary: () => void;
}

function ErrorFallback({ error, resetErrorBoundary }: ErrorFallbackProps) {
  return (
    <div className="min-h-screen flex items-center justify-center p-4">
      <div className="max-w-md w-full">
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertTitle>Something went wrong</AlertTitle>
          <AlertDescription className="mt-2">
            {error.message || "An unexpected error occurred"}
          </AlertDescription>
        </Alert>

        <div className="mt-4 flex gap-2">
          <Button onClick={resetErrorBoundary} variant="outline">
            <RefreshCw className="h-4 w-4 mr-0.5" />
            Try again
          </Button>
          <Button
            onClick={() => window.location.reload()}
            variant="default"
          >
            Reload page
          </Button>
        </div>

        {process.env.NODE_ENV === 'development' && (
          <details className="mt-4 p-4 bg-muted rounded-md">
            <summary className="cursor-pointer font-medium">
              Error details (development only)
            </summary>
            <pre className="mt-2 text-sm text-muted-foreground overflow-auto">
              {error.stack}
            </pre>
          </details>
        )}
      </div>
    </div>
  );
}

interface ErrorBoundaryProps {
  children: ReactNode;
  fallback?: React.ComponentType<ErrorFallbackProps>;
  onError?: (error: Error, errorInfo: ErrorInfo) => void;
}

interface ErrorBoundaryState {
  hasError: boolean;
  error: Error | null;
}

export class ErrorBoundary extends Component<ErrorBoundaryProps, ErrorBoundaryState> {
  constructor(props: ErrorBoundaryProps) {
    super(props);
    this.state = { hasError: false, error: null };
  }

  static getDerivedStateFromError(error: Error): ErrorBoundaryState {
    return { hasError: true, error };
  }

  componentDidCatch(error: Error, errorInfo: ErrorInfo) {
    // Call custom error handler if provided
    this.props.onError?.(error, errorInfo);

    // In production, you might want to send to error reporting service
    // Example: Sentry.captureException(error, { extra: errorInfo });
  }

  resetErrorBoundary = () => {
    this.setState({ hasError: false, error: null });
  };

  render() {
    if (this.state.hasError && this.state.error) {
      const FallbackComponent = this.props.fallback || ErrorFallback;
      return (
        <FallbackComponent
          error={this.state.error}
          resetErrorBoundary={this.resetErrorBoundary}
        />
      );
    }

    return this.props.children;
  }
}

// Specialized error boundary for async operations
export function AsyncErrorBoundary({ children }: { children: ReactNode }) {
  return (
    <ErrorBoundary
      fallback={({ resetErrorBoundary }) => (
        <div className="p-4 text-center">
          <Alert variant="destructive">
            <AlertCircle className="h-4 w-4" />
            <AlertTitle>Loading Error</AlertTitle>
            <AlertDescription>
              Failed to load content. Please try again.
            </AlertDescription>
          </Alert>
          <Button onClick={resetErrorBoundary} className="mt-4">
            Retry
          </Button>
        </div>
      )}
    >
      {children}
    </ErrorBoundary>
  );
}
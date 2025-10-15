import React from 'react';
import { Alert } from './DesignSystem';

class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false, error: null, errorInfo: null };
  }

  static getDerivedStateFromError(error) {
    return { hasError: true };
  }

  componentDidCatch(error, errorInfo) {
    this.setState({
      error: error,
      errorInfo: errorInfo
    });


    // Send error to external logging service if available
    if (window.surecrmErrorLogger && typeof window.surecrmErrorLogger.logError === 'function') {
      try {
        window.surecrmErrorLogger.logError(error, errorInfo);
      } catch (logError) {
        // Failed to log error to external service
      }
    }
  }

  handleRetry = () => {
    this.setState({
      hasError: false,
      error: null,
      errorInfo: null
    });
  };

  render() {
    if (this.state.hasError) {
      const {
        fallback: Fallback,
        showDetails = process.env.NODE_ENV === 'development',
        message,
        showRetry = true
      } = this.props;

      if (Fallback) {
        return <Fallback
          error={this.state.error}
          resetError={this.handleRetry}
          errorInfo={this.state.errorInfo}
        />;
      }

      return (
        <div className="p-6">
          <Alert.Root variant="error">
            <Alert.Title>Something went wrong</Alert.Title>
            <Alert.Description>
              {message || "We're sorry, but something unexpected happened. Please try refreshing the page."}

              {showRetry && (
                <div className="mt-4 flex gap-2">
                  <button
                    onClick={this.handleRetry}
                    className="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                  >
                    Try Again
                  </button>
                  <button
                    onClick={() => window.location.reload()}
                    className="bg-gray-500 text-white px-3 py-1 rounded text-sm hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500"
                  >
                    Reload Page
                  </button>
                </div>
              )}

              {showDetails && this.state.error && (
                <details className="mt-4">
                  <summary className="cursor-pointer font-medium text-red-800 hover:text-red-900">
                    🔍 Error Details (Development Mode)
                  </summary>
                  <div className="mt-2 space-y-2">
                    <div>
                      <strong className="text-red-800">Error:</strong>
                      <pre className="mt-1 text-xs bg-red-100 p-2 rounded overflow-auto border">
                        {this.state.error.toString()}
                      </pre>
                    </div>
                    {this.state.errorInfo?.componentStack && (
                      <div>
                        <strong className="text-red-800">Component Stack:</strong>
                        <pre className="mt-1 text-xs bg-red-100 p-2 rounded overflow-auto border">
                          {this.state.errorInfo.componentStack}
                        </pre>
                      </div>
                    )}
                  </div>
                </details>
              )}
            </Alert.Description>
          </Alert.Root>
        </div>
      );
    }

    return this.props.children;
  }
}

// Higher-order component for functional components
export const withErrorBoundary = (Component, errorFallback) => {
  return function WithErrorBoundaryComponent(props) {
    return (
      <ErrorBoundary fallback={errorFallback}>
        <Component {...props} />
      </ErrorBoundary>
    );
  };
};

/**
 * Page-level Error Boundary
 * Used for wrapping entire pages/views
 */
export const PageErrorBoundary = ({ children, title = "Page" }) => (
  <ErrorBoundary
    message={`There was an error loading the ${title} page. Please try refreshing the page.`}
    fallback={({ error, resetError }) => (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="max-w-md w-full bg-white shadow-lg rounded-lg p-6">
          <div className="flex items-center mb-4">
            <svg className="h-8 w-8 text-red-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            <h1 className="text-lg font-semibold text-gray-900">Oops! Something went wrong</h1>
          </div>
          <p className="text-gray-600 mb-4">
            We encountered an error while loading the {title} page. This might be a temporary issue.
          </p>
          <div className="flex space-x-3">
            <button
              onClick={() => window.location.reload()}
              className="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              Refresh Page
            </button>
            <button
              onClick={resetError}
              className="flex-1 bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500"
            >
              Try Again
            </button>
          </div>
        </div>
      </div>
    )}
  >
    {children}
  </ErrorBoundary>
);

/**
 * Component-level Error Boundary
 * Used for wrapping individual components/sections
 */
export const ComponentErrorBoundary = ({
  children,
  componentName = "component",
  minimal = false
}) => {
  if (minimal) {
    return (
      <ErrorBoundary
        message={`Error loading ${componentName}`}
        showRetry={false}
        fallback={() => (
          <div className="bg-yellow-50 border border-yellow-200 rounded-md p-3">
            <div className="flex">
              <svg className="h-5 w-5 text-yellow-400 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
              </svg>
              <p className="text-sm text-yellow-700">
                Unable to load {componentName}
              </p>
            </div>
          </div>
        )}
      >
        {children}
      </ErrorBoundary>
    );
  }

  return (
    <ErrorBoundary
      message={`There was an error loading the ${componentName}. Please try again.`}
    >
      {children}
    </ErrorBoundary>
  );
};

/**
 * Data Loading Error Boundary
 * Specifically for data fetching errors and API calls
 */
export const DataErrorBoundary = ({ children, dataType = "data" }) => (
  <ErrorBoundary
    message={`Failed to load ${dataType}. Please check your connection and try again.`}
    fallback={({ resetError }) => (
      <div className="bg-gray-50 rounded-lg p-8 text-center">
        <svg className="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h3 className="text-lg font-medium text-gray-900 mb-2">Unable to load {dataType}</h3>
        <p className="text-gray-500 mb-4">
          We're having trouble loading the {dataType}. This could be due to a network issue or server problem.
        </p>
        <div className="flex justify-center gap-3">
          <button
            onClick={resetError}
            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            <svg className="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Retry
          </button>
          <button
            onClick={() => window.location.reload()}
            className="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Refresh Page
          </button>
        </div>
      </div>
    )}
  >
    {children}
  </ErrorBoundary>
);

export default ErrorBoundary;
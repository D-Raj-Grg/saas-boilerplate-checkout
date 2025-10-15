import React from 'react';

// Import components
// Single-page SaaS-first UI
import SaasInfo from './components/SaaSInfo.jsx';
import AuthPage from './components/AuthPage.jsx';
import { ToastContextProvider } from './components/ToastContext.jsx';
import { PageErrorBoundary } from './components/ErrorBoundary.jsx';
import { Tooltip } from './components/DesignSystem.jsx';

const App = () => {

  // Check if authenticated from the data attribute or window object
  const appElement = document.getElementById('surecrm-app');
  const isAuthPage = appElement?.getAttribute('data-page') === 'auth';
  const isAuthenticated = window.surecrmAdmin?.isAuthenticated || false;

  // Show auth page if not authenticated
  if (!isAuthenticated || isAuthPage) {
    return (
      <PageErrorBoundary title="Authentication">
        <Tooltip.Provider>
          <ToastContextProvider>
            <div className="surecrm-app">
              <AuthPage />
            </div>
          </ToastContextProvider>
        </Tooltip.Provider>
      </PageErrorBoundary>
    );
  }

  // Single connected page: show SaaS info for any plugin page
  const component = <SaasInfo />;
  const pageTitle = "SureCRM Dashboard";

  return (
    <PageErrorBoundary title={pageTitle}>
      <Tooltip.Provider>
        <ToastContextProvider>
          <div className="surecrm-app">
            {component}
          </div>
        </ToastContextProvider>
      </Tooltip.Provider>
    </PageErrorBoundary>
  );
};

export default App;

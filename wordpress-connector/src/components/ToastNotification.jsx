import React from 'react';
import * as Toast from '@radix-ui/react-toast';

// ToastProvider component to be used at the app root
export const ToastProvider = ({ children }) => {
  return (
    <Toast.Provider swipeDirection="right" duration={5000}>
      {children}
      <Toast.Viewport className="fixed top-0 right-0 flex flex-col p-6 gap-2 w-full max-w-sm z-[99999]" />
    </Toast.Provider>
  );
};

// ToastNotification component for displaying toast messages
const ToastNotification = ({ 
  open, 
  setOpen, 
  title, 
  description, 
  type = 'success', // 'success', 'error', 'warning', 'info'
  duration = 5000, // in milliseconds
}) => {
  // Define colors and icons based on type
  const typeStyles = {
    success: {
      background: 'bg-green-50',
      border: 'border-green-200',
      title: 'text-green-800',
      description: 'text-green-700',
      icon: (
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-green-500">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
          <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
      )
    },
    error: {
      background: 'bg-red-50',
      border: 'border-red-200',
      title: 'text-red-800',
      description: 'text-red-700',
      icon: (
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-red-500">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="15" y1="9" x2="9" y2="15"></line>
          <line x1="9" y1="9" x2="15" y2="15"></line>
        </svg>
      )
    },
    warning: {
      background: 'bg-yellow-50',
      border: 'border-yellow-200',
      title: 'text-yellow-800',
      description: 'text-yellow-700',
      icon: (
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-yellow-500">
          <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
          <line x1="12" y1="9" x2="12" y2="13"></line>
          <line x1="12" y1="17" x2="12.01" y2="17"></line>
        </svg>
      )
    },
    info: {
      background: 'bg-blue-50',
      border: 'border-blue-200',
      title: 'text-blue-800',
      description: 'text-blue-700',
      icon: (
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-blue-500">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="16" x2="12" y2="12"></line>
          <line x1="12" y1="8" x2="12.01" y2="8"></line>
        </svg>
      )
    }
  };

  const styles = typeStyles[type] || typeStyles.info;

  return (
    <Toast.Root
      className={`${styles.background} ${styles.border} border rounded-md shadow-lg p-4 flex items-start gap-3 mt-6`}
      open={open}
      onOpenChange={setOpen}
      duration={duration}
    >
      <div className="flex-shrink-0 mt-0.5">
        {styles.icon}
      </div>
      <div className="flex-1">
        {title && (
          <Toast.Title className={`text-sm font-medium ${styles.title}`}>
            {title}
          </Toast.Title>
        )}
        {description && (
          <Toast.Description className={`mt-1 text-sm ${styles.description}`}>
            {description}
          </Toast.Description>
        )}
      </div>
      <Toast.Close className="flex-shrink-0 self-start text-gray-400 hover:text-gray-500">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
        <span className="sr-only">Close</span>
      </Toast.Close>
    </Toast.Root>
  );
};

export default ToastNotification;

import React, { createContext, useState, useContext, useCallback, useRef } from 'react';
import ToastNotification, { ToastProvider } from './ToastNotification';

// Create context
const ToastContext = createContext(null);

// Toast provider component
export const ToastContextProvider = ({ children }) => {
  const [toasts, setToasts] = useState([]);
  const recentToastsRef = useRef({});

  // Function to add a toast with deduplication
  const addToast = useCallback(({ title, description, type = 'info', duration = 5000 }) => {
    // Create a unique key for this toast based on its content
    const toastKey = `${type}:${title}:${description}`;

    // Check if we've shown this exact toast recently (within 3 seconds)
    const now = Date.now();
    if (recentToastsRef.current[toastKey] && (now - recentToastsRef.current[toastKey]) < 3000) {
      // Skip creating duplicate toast
      return null;
    }

    // Record this toast as recently shown
    recentToastsRef.current[toastKey] = now;

    // Clean up old entries from recentToastsRef (older than 10 seconds)
    Object.keys(recentToastsRef.current).forEach(key => {
      if (now - recentToastsRef.current[key] > 10000) {
        delete recentToastsRef.current[key];
      }
    });

    const id = now;
    setToasts(prev => [...prev, { id, title, description, type, duration, open: true }]);
    return id;
  }, []);

  // Function to remove a toast
  const removeToast = useCallback((id) => {
    setToasts(prev => prev.map(toast => 
      toast.id === id ? { ...toast, open: false } : toast
    ));

    // Remove from state after animation completes
    setTimeout(() => {
      setToasts(prev => prev.filter(toast => toast.id !== id));
    }, 300);
  }, []);

  // Convenience functions for different toast types
  const toast = {
    success: (title, description, duration) => 
      addToast({ title, description, type: 'success', duration }),
    error: (title, description, duration) => 
      addToast({ title, description, type: 'error', duration }),
    warning: (title, description, duration) => 
      addToast({ title, description, type: 'warning', duration }),
    info: (title, description, duration) => 
      addToast({ title, description, type: 'info', duration }),
  };

  return (
    <ToastContext.Provider value={{ toast, removeToast }}>
      <ToastProvider>
        {children}
        {toasts.map(({ id, title, description, type, duration, open }) => (
          <ToastNotification
            key={id}
            open={open}
            setOpen={(open) => {
              if (!open) removeToast(id);
            }}
            title={title}
            description={description}
            type={type}
            duration={duration}
          />
        ))}
      </ToastProvider>
    </ToastContext.Provider>
  );
};

// Custom hook to use the toast context
export const useToast = () => {
  const context = useContext(ToastContext);
  if (!context) {
    throw new Error('useToast must be used within a ToastContextProvider');
  }
  return context.toast;
};

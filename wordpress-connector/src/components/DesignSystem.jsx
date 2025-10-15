import React from 'react';
import * as RadixTooltip from '@radix-ui/react-tooltip';
import * as RadixSeparator from '@radix-ui/react-separator';
import * as RadixDialog from '@radix-ui/react-dialog';
import * as RadixAlertDialog from '@radix-ui/react-alert-dialog';
import * as RadixSelect from '@radix-ui/react-select';
import * as RadixSwitch from '@radix-ui/react-switch';
import * as RadixSlider from '@radix-ui/react-slider';
import { Slot } from '@radix-ui/react-slot';

// Enhanced Design System with consistent spacing and layout patterns
const SPACING = {
  // Consistent spacing scale following 8pt grid system
  '0': '0',
  '1': '0.25rem',  // 4px
  '2': '0.5rem',   // 8px
  '3': '0.75rem',  // 12px
  '4': '1rem',     // 16px
  '5': '1.25rem',  // 20px
  '6': '1.5rem',   // 24px
  '8': '2rem',     // 32px
  '10': '2.5rem',  // 40px
  '12': '3rem',    // 48px
  '16': '4rem',    // 64px
  '20': '5rem',    // 80px
  '24': '6rem',    // 96px
};

const COLORS = {
  // Using CSS variables for consistent theming
  background: 'var(--background)',
  foreground: 'var(--foreground)',
  card: 'var(--card)',
  'card-foreground': 'var(--card-foreground)',
  popover: 'var(--popover)',
  'popover-foreground': 'var(--popover-foreground)',
  primary: 'var(--primary)',
  'primary-foreground': 'var(--primary-foreground)',
  secondary: 'var(--secondary)',
  'secondary-foreground': 'var(--secondary-foreground)',
  muted: 'var(--muted)',
  'muted-foreground': 'var(--muted-foreground)',
  accent: 'var(--accent)',
  'accent-foreground': 'var(--accent-foreground)',
  destructive: 'var(--destructive)',
  'destructive-foreground': 'var(--destructive-foreground)',
  border: 'var(--border)',
  input: 'var(--input)',
  ring: 'var(--ring)',
  // Chart colors
  chart: {
    1: 'var(--chart-1)',
    2: 'var(--chart-2)',
    3: 'var(--chart-3)',
    4: 'var(--chart-4)',
    5: 'var(--chart-5)'
  },
  // Sidebar colors
  sidebar: 'var(--sidebar)',
  'sidebar-foreground': 'var(--sidebar-foreground)',
  'sidebar-primary': 'var(--sidebar-primary)',
  'sidebar-primary-foreground': 'var(--sidebar-primary-foreground)',
  'sidebar-accent': 'var(--sidebar-accent)',
  'sidebar-accent-foreground': 'var(--sidebar-accent-foreground)',
  'sidebar-border': 'var(--sidebar-border)',
  'sidebar-ring': 'var(--sidebar-ring)'
};

const LAYOUT = {
  container: {
    maxWidth: '1280px',
    margin: '0 auto',
    padding: '0 1.5rem'
  },
  section: {
    marginBottom: SPACING['6'], // Standardized to space-6
    spacing: SPACING['6']
  },
  card: {
    padding: SPACING['6'], // Standardized to p-6
    marginBottom: SPACING['6'],
    borderRadius: 'var(--radius)', // Use CSS variable for consistent radius
    border: `1px solid ${COLORS.border}`,
    backgroundColor: COLORS.card,
    color: COLORS['card-foreground'],
    shadow: 'var(--shadow-sm)' // Use CSS variable for consistent shadows
  },
  input: {
    padding: `${SPACING['3']} ${SPACING['4']}`,
    marginBottom: SPACING['4'],
    borderRadius: 'var(--radius)',
    backgroundColor: COLORS.background,
    borderColor: COLORS.border
  },
  button: {
    paddingX: SPACING['4'],
    paddingY: SPACING['2'],
    borderRadius: 'var(--radius)', // Use CSS variable for consistent radius
    fontSize: '0.875rem', // text-sm
    fontWeight: '500' // font-medium
  }
};

// Layout Container Component
const Container = React.forwardRef(({ size = 'default', className = '', children, ...props }, ref) => {
  const sizeClasses = {
    sm: 'max-w-3xl',
    default: 'max-w-none lg:max-w-[90vw] xl:max-w-[85vw] 2xl:max-w-[80vw]',
    lg: 'max-w-full',
    full: 'w-full'
  };

  return (
    <div
      ref={ref}
      className={`mx-auto px-4 sm:px-6 lg:px-8 ${sizeClasses[size]} ${className}`}
      {...props}
    >
      {children}
    </div>
  );
});

// Improved Box component for consistent spacing
const Box = React.forwardRef(({
  p, px, py, pt, pr, pb, pl,
  m, mx, my, mt, mr, mb, ml,
  className = '',
  children,
  ...props
}, ref) => {
  const spacingClasses = [];

  // Padding classes
  if (p) spacingClasses.push(`p-${p}`);
  if (px) spacingClasses.push(`px-${px}`);
  if (py) spacingClasses.push(`py-${py}`);
  if (pt) spacingClasses.push(`pt-${pt}`);
  if (pr) spacingClasses.push(`pr-${pr}`);
  if (pb) spacingClasses.push(`pb-${pb}`);
  if (pl) spacingClasses.push(`pl-${pl}`);

  // Margin classes
  if (m) spacingClasses.push(`m-${m}`);
  if (mx) spacingClasses.push(`mx-${mx}`);
  if (my) spacingClasses.push(`my-${my}`);
  if (mt) spacingClasses.push(`mt-${mt}`);
  if (mr) spacingClasses.push(`mr-${mr}`);
  if (mb) spacingClasses.push(`mb-${mb}`);
  if (ml) spacingClasses.push(`ml-${ml}`);

  return (
    <div
      ref={ref}
      className={`${spacingClasses.join(' ')} ${className}`}
      {...props}
    >
      {children}
    </div>
  );
});

// Enhanced Button Component with modern styling and better states
const Button = {
  Root: React.forwardRef(({
    variant = 'primary',
    size = 'md',
    fullWidth = false,
    loading = false,
    icon = null,
    children,
    className = '',
    disabled = false,
    asChild = false,
    ...props
  }, ref) => {
    const baseClasses = 'surecrm-button inline-flex items-center justify-center font-normal focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed border border-transparent';

    const variants = {
      primary: 'bg-primary text-primary-foreground hover:bg-primary/90 focus:ring-ring border-primary transition-colors',
      secondary: 'bg-secondary text-secondary-foreground hover:bg-secondary/80 focus:ring-ring border-secondary transition-colors',
      success: 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500 shadow border-green-600 transition-colors',
      danger: 'bg-destructive text-destructive-foreground hover:bg-destructive/90 focus:ring-ring shadow border-destructive transition-colors',
      warning: 'bg-yellow-600 text-white hover:bg-yellow-700 focus:ring-yellow-500 shadow border-yellow-600 transition-colors',
      ghost: 'text-muted-foreground hover:text-foreground hover:bg-accent focus:ring-ring border-transparent transition-colors',
      link: 'text-primary hover:text-primary/80 underline focus:ring-ring transition-colors'
    };

    const sizes = {
      xs: 'px-2.5 py-1 text-xs rounded-md gap-1 min-h-[24px]',
      sm: 'px-3 py-1.5 text-sm rounded-md gap-1.5 min-h-[28px]',
      md: 'px-4 py-2 text-sm rounded-md gap-2 min-h-[32px]',
      lg: 'px-5 py-2.5 text-base rounded-md gap-2 min-h-[36px]',
      xl: 'px-6 py-3 text-lg rounded-md gap-2.5 min-h-[40px]'
    };

    const widthClass = fullWidth ? 'w-full' : '';
    const disabledClass = (disabled || loading) ? 'opacity-50 cursor-not-allowed' : '';

    const Comp = asChild ? Slot : 'button';

    // Build the props object to handle disabled state properly
    const buttonProps = {
      ref,
      className: `${baseClasses} ${variants[variant]} ${sizes[size]} ${widthClass} ${disabledClass} ${className}`,
      ...props
    };

    // Only add disabled attribute to button elements, not anchors
    if (!asChild) {
      buttonProps.disabled = disabled || loading;
    }

    // When using asChild, we need to clone the child and add our props/children to it
    if (asChild && React.isValidElement(children)) {
      return (
        <Slot {...buttonProps}>
          {React.cloneElement(children, {},
            <>
              {loading && (
                <svg className="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
              )}
              {icon && !loading && icon}
              {children.props.children}
            </>
          )}
        </Slot>
      );
    }

    return (
      <Comp {...buttonProps}>
        {loading && (
          <svg className="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        )}
        {icon && !loading && icon}
        {children}
      </Comp>
    );
  })
};

// Enhanced Card Component with modern styling and CSS variables
const Card = {
  Root: React.forwardRef(({ variant = 'default', padding = 'lg', className = '', ...props }, ref) => {
    const baseClasses = 'bg-card text-card-foreground border border-border rounded-md overflow-hidden shadow-sm';

    const variants = {
      default: '',
      elevated: 'shadow-lg',
      outlined: 'border-2',
      ghost: 'bg-transparent shadow-none border-0',
      glass: 'shadow-xl'
    };

    const paddingClasses = {
      none: '',
      sm: 'p-3',
      default: 'p-4',
      lg: 'p-6'
    };

    return (
      <div
        ref={ref}
        className={`${baseClasses} ${variants[variant]} ${paddingClasses[padding]} ${className}`}
        {...props}
      />
    );
  }),

  Header: React.forwardRef(({ className = '', ...props }, ref) => (
    <div
      ref={ref}
      className={`px-4 py-3 border-b border-border ${className}`}
      {...props}
    />
  )),

  Content: React.forwardRef(({ padding = 'lg', className = '', ...props }, ref) => {
    const paddingClasses = {
      none: '',
      sm: 'p-3',
      default: 'p-4',
      lg: 'p-6'
    };

    return (
      <div
        ref={ref}
        className={`${paddingClasses[padding]} ${className}`}
        {...props}
      />
    );
  }),

  Footer: React.forwardRef(({ className = '', ...props }, ref) => (
    <div
      ref={ref}
      className={`px-4 py-3 bg-muted border-t border-border backdrop-blur-sm ${className}`}
      {...props}
    />
  ))
};

// Enhanced Input Components with modern styling
const Input = {
  Group: React.forwardRef(({ spacing = 'default', className = '', ...props }, ref) => {
    const spacingClasses = {
      tight: 'space-y-3',
      default: 'space-y-5',
      loose: 'space-y-7'
    };

    return (
      <div
        ref={ref}
        className={`${spacingClasses[spacing]} ${className}`}
        {...props}
      />
    );
  }),

  Root: React.forwardRef(({ className = '', ...props }, ref) => (
    <div
      ref={ref}
      className={`relative ${className}`}
      {...props}
    />
  )),

  Label: React.forwardRef(({ required = false, className = '', children, ...props }, ref) => (
    <label
      ref={ref}
      className={`block text-sm font-medium text-foreground mb-2 ${className}`}
      {...props}
    >
      {children}
      {required && <span className="text-destructive ml-1">*</span>}
    </label>
  )),

  Input: React.forwardRef(({ size = 'md', error = false, className = '', ...props }, ref) => {
    const baseClasses = 'w-full border backdrop-blur-sm text-sm placeholder:text-muted-foreground transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-offset-1 bg-background hover:bg-background';

    const sizes = {
      sm: 'px-4 py-2.5 rounded-md text-sm',
      md: 'px-4 py-3 rounded-md text-sm',
      lg: 'px-5 py-3.5 rounded-md text-base'
    };

    const errorClasses = error
      ? 'border-destructive focus:border-destructive focus:ring-destructive/20 shadow-sm'
      : 'border-border focus:border-ring focus:ring-ring/20 hover:border-accent-foreground shadow-sm hover:shadow-md';

    return (
      <input
        ref={ref}
        className={`${baseClasses} ${sizes[size]} ${errorClasses} ${className}`}
        {...props}
      />
    );
  }),

  Error: React.forwardRef(({ className = '', ...props }, ref) => (
    <div
      ref={ref}
      className={`mt-2 text-sm text-destructive ${className}`}
      {...props}
    />
  )),

  Help: React.forwardRef(({ className = '', ...props }, ref) => (
    <div
      ref={ref}
      className={`mt-2 text-xs text-muted-foreground ${className}`}
      {...props}
    />
  ))
};

// Enhanced Typography with consistent spacing
const Typography = {
  H1: React.forwardRef(({ className = '', ...props }, ref) => (
    <h1
      ref={ref}
      className={`text-2xl font-semibold ${className}`}
      {...props}
    />
  )),

  H2: React.forwardRef(({ className = '', ...props }, ref) => (
    <h2
      ref={ref}
      className={`text-2xl font-medium mb-4 ${className}`}
      {...props}
    />
  )),

  H3: React.forwardRef(({ className = '', ...props }, ref) => (
    <h3
      ref={ref}
      className={`text-lg font-medium mb-4 ${className}`}
      {...props}
    />
  )),

  H4: React.forwardRef(({ className = '', ...props }, ref) => (
    <h4
      ref={ref}
      className={`text-base font-medium mb-2 ${className}`}
      {...props}
    />
  )),

  Body: React.forwardRef(({ size = 'md', className = '', ...props }, ref) => {
    const sizes = {
      xs: 'text-xs',
      sm: 'text-sm',
      md: 'text-base',
      lg: 'text-lg'
    };

    return (
      <p
        ref={ref}
        className={`${sizes[size]} text-muted-foreground ${className}`}
        {...props}
      />
    );
  }),

  Caption: React.forwardRef(({ className = '', ...props }, ref) => (
    <span
      ref={ref}
      className={`text-xs text-muted-foreground ${className}`}
      {...props}
    />
  ))
};

// Enhanced Badge with modern styling and animations
const Badge = React.forwardRef(({
  variant = 'default',
  size = 'md',
  withDot = false,
  pulse = false,
  className = '',
  children,
  ...props
}, ref) => {
  const baseClasses = 'inline-flex items-center font-medium rounded-full border';

  const variants = {
    default: 'bg-muted text-muted-foreground border-border',
    primary: 'bg-primary/10 text-primary border-primary/20',
    success: 'bg-green-100 text-green-800 border-green-200',
    warning: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    error: 'bg-destructive/10 text-destructive border-destructive/20',
    info: 'bg-blue-100 text-blue-800 border-blue-200'
  };

  const sizes = {
    sm: 'px-3 py-1.5 text-xs gap-1.5',
    md: 'px-3.5 py-2 text-xs gap-2',
    lg: 'px-4 py-2.5 text-sm gap-2'
  };

  const dotColors = {
    default: 'bg-gray-500',
    primary: 'bg-indigo-500',
    success: 'bg-green-500',
    warning: 'bg-yellow-500',
    error: 'bg-red-500',
    info: 'bg-blue-500'
  };

  const pulseClass = '';

  return (
    <span
      ref={ref}
      className={`${baseClasses} ${variants[variant]} ${sizes[size]} ${pulseClass} ${className}`}
      {...props}
    >
      {withDot && (
        <span className={`w-2 h-2 rounded-full ${dotColors[variant]}`}></span>
      )}
      {children}
    </span>
  );
});

// Stack component for consistent vertical spacing
const Stack = React.forwardRef(({
  space = '4',
  align = 'stretch',
  justify = 'start',
  direction = 'vertical',
  className = '',
  ...props
}, ref) => {
  const directionClasses = {
    vertical: 'flex flex-col',
    horizontal: 'flex flex-row'
  };

  const spaceClasses = {
    vertical: {
      '1': 'space-y-1',
      '1.5': 'space-y-1.5',
      '2': 'space-y-2',
      '3': 'space-y-3',
      '4': 'space-y-4',
      '5': 'space-y-5',
      '6': 'space-y-6',
      '8': 'space-y-8'
    },
    horizontal: {
      '1': 'space-x-1',
      '1.5': 'space-x-1.5',
      '2': 'space-x-2',
      '3': 'space-x-3',
      '4': 'space-x-4',
      '5': 'space-x-5',
      '6': 'space-x-6',
      '8': 'space-x-8'
    }
  };

  const alignClasses = {
    start: 'items-start',
    center: 'items-center',
    end: 'items-end',
    stretch: 'items-stretch'
  };

  const justifyClasses = {
    start: 'justify-start',
    center: 'justify-center',
    end: 'justify-end',
    between: 'justify-between',
    around: 'justify-around'
  };

  return (
    <div
      ref={ref}
      className={`${directionClasses[direction] || 'flex flex-col'} ${spaceClasses[direction]?.[space] || ''} ${alignClasses[align] || 'items-stretch'} ${justifyClasses[justify] || 'justify-start'} ${className}`}
      {...props}
    />
  );
});

// Improved Separator using Radix
const Separator = React.forwardRef(({ orientation = 'horizontal', className = '', ...props }, ref) => (
  <RadixSeparator.Root
    ref={ref}
    orientation={orientation}
    className={`bg-border ${orientation === 'horizontal' ? 'h-px w-full my-6' : 'w-px h-full mx-6'} ${className}`}
    {...props}
  />
));

// Grid component for layout consistency
const Grid = React.forwardRef(({
  cols = 1,
  gap = '6',
  responsive = true,
  className = '',
  ...props
}, ref) => {
  const colClasses = {
    1: 'grid-cols-1',
    2: 'grid-cols-1 md:grid-cols-2',
    3: 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
    4: 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
    6: 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6'
  };

  const gapClasses = {
    '2': 'gap-2',
    '4': 'gap-4',
    '6': 'gap-6',
    '8': 'gap-8'
  };

  const responsiveClass = responsive ? colClasses[cols] : `grid-cols-${cols}`;

  return (
    <div
      ref={ref}
      className={`grid ${responsiveClass} ${gapClasses[gap]} ${className}`}
      {...props}
    />
  );
});

// Enhanced Alert with modern styling and animations
const Alert = {
  Root: React.forwardRef(({ variant = 'info', className = '', ...props }, ref) => {
    const variants = {
      info: 'bg-gradient-to-r from-blue-50/80 to-blue-100/60 border-blue-200/60 text-blue-800 shadow-sm shadow-blue-100/50',
      success: 'bg-gradient-to-r from-green-50/80 to-green-100/60 border-green-200/60 text-green-800 shadow-sm shadow-green-100/50',
      warning: 'bg-gradient-to-r from-yellow-50/80 to-yellow-100/60 border-yellow-200/60 text-yellow-800 shadow-sm shadow-yellow-100/50',
      error: 'bg-gradient-to-r from-red-50/80 to-red-100/60 border-red-200/60 text-red-800 shadow-sm shadow-red-100/50'
    };

    return (
      <div
        ref={ref}
        className={`border rounded-2xl p-5 backdrop-blur-sm transition-all duration-300 ${variants[variant]} ${className}`}
        {...props}
      />
    );
  }),

  Title: React.forwardRef(({ className = '', ...props }, ref) => (
    <h4
      ref={ref}
      className={`font-medium text-sm mb-2 ${className}`}
      {...props}
    />
  )),

  Description: React.forwardRef(({ className = '', ...props }, ref) => (
    <p
      ref={ref}
      className={`text-sm leading-relaxed ${className}`}
      {...props}
    />
  ))
};

// Enhanced Spinner with consistent sizing
const Spinner = React.forwardRef(({ size = 'md', className = '', ...props }, ref) => {
  const sizes = {
    xs: 'h-3 w-3',
    sm: 'h-4 w-4',
    md: 'h-5 w-5',
    lg: 'h-6 w-6',
    xl: 'h-8 w-8'
  };

  return (
    <svg
      ref={ref}
      className={`animate-spin ${sizes[size]} ${className}`}
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
      {...props}
    >
      <circle
        className="opacity-25"
        cx="12"
        cy="12"
        r="10"
        stroke="currentColor"
        strokeWidth="4"
      ></circle>
      <path
        className="opacity-75"
        fill="currentColor"
        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
      ></path>
    </svg>
  );
});

// Enhanced Progress Bar
const Progress = React.forwardRef(({ value = 0, max = 100, size = 'md', variant = 'primary', showLabel = false, className = '', ...props }, ref) => {
  const percentage = Math.min(Math.max((value / max) * 100, 0), 100);

  const sizes = {
    sm: 'h-2',
    md: 'h-3',
    lg: 'h-4'
  };

  const variants = {
    primary: 'bg-indigo-500',
    success: 'bg-green-500',
    warning: 'bg-yellow-500',
    error: 'bg-red-500'
  };

  return (
    <div ref={ref} className={`relative w-full ${className}`} {...props}>
      <div className={`w-full bg-gray-200 rounded-full overflow-hidden ${sizes[size]}`}>
        <div
          className={`h-full transition-all duration-500 ease-out ${variants[variant]}`}
          style={{ width: `${percentage}%` }}
        />
      </div>
      {showLabel && (
        <div className="mt-1 text-xs text-gray-600 text-right">
          {Math.round(percentage)}%
        </div>
      )}
    </div>
  );
});

// Skeleton Loader Components
const Skeleton = {
  Root: React.forwardRef(({ className = '', ...props }, ref) => (
    <div
      ref={ref}
      className={`animate-pulse bg-gray-200 rounded ${className}`}
      {...props}
    />
  )),

  Text: React.forwardRef(({ lines = 1, className = '', ...props }, ref) => (
    <div ref={ref} className={`space-y-2 ${className}`} {...props}>
      {Array.from({ length: lines }).map((_, i) => (
        <div
          key={i}
          className={`animate-pulse bg-gray-200 rounded h-4 ${i === lines - 1 && lines > 1 ? 'w-3/4' : 'w-full'
            }`}
        />
      ))}
    </div>
  )),

  Circle: React.forwardRef(({ size = 'md', className = '', ...props }, ref) => {
    const sizes = {
      sm: 'w-8 h-8',
      md: 'w-12 h-12',
      lg: 'w-16 h-16'
    };

    return (
      <div
        ref={ref}
        className={`animate-pulse bg-gray-200 rounded-full ${sizes[size]} ${className}`}
        {...props}
      />
    );
  })
};

// Enhanced Table Components
const Table = {
  Root: React.forwardRef(({ className = '', ...props }, ref) => (
    <div ref={ref} className={`overflow-hidden border border-gray-200 rounded-xl ${className}`} {...props}>
      <table className="min-w-full divide-y divide-gray-200">
        {props.children}
      </table>
    </div>
  )),

  Header: React.forwardRef(({ className = '', ...props }, ref) => (
    <thead ref={ref} className={`bg-gray-50 ${className}`} {...props} />
  )),

  Body: React.forwardRef(({ className = '', ...props }, ref) => (
    <tbody ref={ref} className={`bg-white divide-y divide-gray-200 ${className}`} {...props} />
  )),

  Row: React.forwardRef(({ className = '', hover = true, ...props }, ref) => (
    <tr
      ref={ref}
      className={`${hover ? 'hover:bg-gray-50' : ''} transition-colors ${className}`}
      {...props}
    />
  )),

  HeaderCell: React.forwardRef(({ className = '', ...props }, ref) => (
    <th
      ref={ref}
      className={`px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider ${className}`}
      {...props}
    />
  )),

  Cell: React.forwardRef(({ className = '', ...props }, ref) => (
    <td
      ref={ref}
      className={`px-6 py-4 whitespace-nowrap text-sm text-gray-900 ${className}`}
      {...props}
    />
  ))
};

// Enhanced Tabs Component
const Tabs = {
  Root: React.forwardRef(({ className = '', ...props }, ref) => (
    <div ref={ref} className={className} {...props} />
  )),

  List: React.forwardRef(({ className = '', variant = 'default', ...props }, ref) => {
    const variants = {
      default: 'border-b border-gray-200',
      pills: 'bg-gray-100 p-1 rounded-lg',
      underline: 'border-b-2 border-gray-100'
    };

    return (
      <div
        ref={ref}
        className={`flex ${variants[variant]} ${className}`}
        {...props}
      />
    );
  }),

  Trigger: React.forwardRef(({
    active = false,
    variant = 'default',
    className = '',
    ...props
  }, ref) => {
    const baseClasses = 'px-4 py-2 text-sm font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500';

    const variants = {
      default: active
        ? 'border-b-2 border-indigo-500 text-indigo-600 bg-white'
        : 'border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
      pills: active
        ? 'bg-white text-indigo-600 shadow-sm rounded-md'
        : 'text-gray-600 hover:text-gray-900 rounded-md hover:bg-white/50',
      underline: active
        ? 'border-b-2 border-indigo-500 text-indigo-600'
        : 'border-b-2 border-transparent text-gray-500 hover:text-gray-700'
    };

    return (
      <button
        ref={ref}
        className={`${baseClasses} ${variants[variant]} ${className}`}
        {...props}
      />
    );
  }),

  Content: React.forwardRef(({ className = '', ...props }, ref) => (
    <div ref={ref} className={`mt-4 ${className}`} {...props} />
  ))
};

// Stat Card Component for dashboards
const StatCard = React.forwardRef(({
  title,
  value,
  change,
  changeType = 'neutral',
  icon,
  trend,
  loading = false,
  className = '',
  ...props
}, ref) => {
  const changeColors = {
    positive: 'text-green-600 bg-green-100',
    negative: 'text-red-600 bg-red-100',
    neutral: 'text-gray-600 bg-gray-100'
  };

  if (loading) {
    return (
      <Card.Root ref={ref} className={`p-6 ${className}`} {...props}>
        <div className="flex items-center justify-between">
          <div className="flex-1">
            <Skeleton.Text />
            <div className="mt-2">
              <Skeleton.Root className="h-8 w-24" />
            </div>
          </div>
          <Skeleton.Circle size="sm" />
        </div>
      </Card.Root>
    );
  }

  return (
    <Card.Root ref={ref} className={`p-6 ${className}`} {...props}>
      <div className="flex items-center justify-between">
        <div className="flex-1">
          <Typography.Body size="sm" className="text-gray-600 font-medium">
            {title}
          </Typography.Body>
          <div className="mt-2 flex items-baseline">
            <Typography.H3 className="text-2xl font-semibold text-gray-900 mb-0">
              {value}
            </Typography.H3>
            {change && (
              <span className={`ml-2 px-2 py-1 text-xs rounded-full ${changeColors[changeType]}`}>
                {change}
              </span>
            )}
          </div>
        </div>
        {icon && (
          <div className="flex-shrink-0">
            <div className="p-3 bg-indigo-100 rounded-lg">
              {icon}
            </div>
          </div>
        )}
      </div>
      {trend && (
        <div className="mt-4">
          {trend}
        </div>
      )}
    </Card.Root>
  );
});

// Tooltip Component
const TooltipContent = React.forwardRef(({ className = '', sideOffset = 5, ...props }, ref) => (
  <RadixTooltip.Portal>
    <RadixTooltip.Content
      ref={ref}
      sideOffset={sideOffset}
      className={`
        surecrm-tooltip-content
        z-[100000] 
        max-w-sm 
        px-3 
        py-2 
        text-xs 
        leading-normal
        text-gray-700 
        bg-white 
        border 
        border-gray-200 
        rounded-md 
        shadow-md
        animate-in 
        slide-in-from-bottom-2 
        duration-200
        ${className}
      `}
      {...props}
    >
      {props.children}
      <RadixTooltip.Arrow className="fill-white stroke-gray-200" width={8} height={4} />
    </RadixTooltip.Content>
  </RadixTooltip.Portal>
));
TooltipContent.displayName = 'TooltipContent';

const Tooltip = {
  Provider: RadixTooltip.Provider,
  Root: RadixTooltip.Root,
  Trigger: RadixTooltip.Trigger,
  Content: TooltipContent,
};

// Info Icon for tooltips
const InfoIcon = ({ className = "", size = 16 }) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={size}
    height={size}
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="2"
    strokeLinecap="round"
    strokeLinejoin="round"
    className={`inline-block ${className}`}
  >
    <circle cx="12" cy="12" r="10"></circle>
    <line x1="12" y1="16" x2="12" y2="12"></line>
    <line x1="12" y1="8" x2="12.01" y2="8"></line>
  </svg>
);

// Dialog Component
const Dialog = {
  Root: RadixDialog.Root,
  Trigger: React.forwardRef(({ className = '', ...props }, ref) => (
    <RadixDialog.Trigger
      ref={ref}
      className={`inline-flex items-center justify-center ${className}`}
      {...props}
    />
  )),
  Portal: RadixDialog.Portal,
  Overlay: React.forwardRef(({ className = '', ...props }, ref) => (
    <RadixDialog.Overlay
      ref={ref}
      className={`fixed inset-0 z-50 bg-black/50 backdrop-blur-sm transition-opacity ${className}`}
      {...props}
    />
  )),
  Content: React.forwardRef(({ className = '', ...props }, ref) => (
    <RadixDialog.Content
      ref={ref}
      className={`fixed left-1/2 top-1/2 z-50 w-full max-w-lg -translate-x-1/2 -translate-y-1/2 bg-card text-card-foreground p-6 shadow-lg rounded-md border border-border ${className}`}
      {...props}
    />
  )),
  Header: React.forwardRef(({ className = '', ...props }, ref) => (
    <div ref={ref} className={`mb-4 ${className}`} {...props} />
  )),
  Title: React.forwardRef(({ className = '', ...props }, ref) => (
    <RadixDialog.Title
      ref={ref}
      className={`text-lg font-semibold text-foreground ${className}`}
      {...props}
    />
  )),
  Description: React.forwardRef(({ className = '', ...props }, ref) => (
    <RadixDialog.Description
      ref={ref}
      className={`text-sm text-muted-foreground mt-2 ${className}`}
      {...props}
    />
  )),
  Footer: React.forwardRef(({ className = '', ...props }, ref) => (
    <div ref={ref} className={`flex justify-end gap-3 mt-6 ${className}`} {...props} />
  )),
  Close: React.forwardRef(({ className = '', ...props }, ref) => (
    <RadixDialog.Close
      ref={ref}
      className={`absolute right-4 top-4 rounded-sm opacity-70 hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 ${className}`}
      {...props}
    />
  ))
};

// AlertDialog Component
const AlertDialog = {
  Root: RadixAlertDialog.Root,
  Trigger: React.forwardRef(({ className = '', ...props }, ref) => (
    <RadixAlertDialog.Trigger
      ref={ref}
      className={`inline-flex items-center justify-center ${className}`}
      {...props}
    />
  )),
  Portal: RadixAlertDialog.Portal,
  Overlay: React.forwardRef(({ className = '', ...props }, ref) => (
    <RadixAlertDialog.Overlay
      ref={ref}
      className={`fixed inset-0 z-50 bg-black/50 backdrop-blur-sm transition-opacity ${className}`}
      {...props}
    />
  )),
  Content: React.forwardRef(({ className = '', ...props }, ref) => (
    <RadixAlertDialog.Content
      ref={ref}
      className={`fixed left-1/2 top-1/2 z-50 w-full max-w-lg -translate-x-1/2 -translate-y-1/2 bg-white p-6 shadow-lg rounded-xl border border-gray-200 ${className}`}
      {...props}
    />
  )),
  Header: React.forwardRef(({ className = '', ...props }, ref) => (
    <div ref={ref} className={`mb-4 ${className}`} {...props} />
  )),
  Title: React.forwardRef(({ className = '', ...props }, ref) => (
    <RadixAlertDialog.Title
      ref={ref}
      className={`text-lg font-semibold text-gray-900 ${className}`}
      {...props}
    />
  )),
  Description: React.forwardRef(({ className = '', ...props }, ref) => (
    <RadixAlertDialog.Description
      ref={ref}
      className={`text-sm text-gray-600 mt-2 ${className}`}
      {...props}
    />
  )),
  Footer: React.forwardRef(({ className = '', ...props }, ref) => (
    <div ref={ref} className={`flex justify-end gap-3 mt-6 ${className}`} {...props} />
  )),
  Cancel: React.forwardRef(({ className = '', ...props }, ref) => (
    <RadixAlertDialog.Cancel
      ref={ref}
      className={`inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 ${className}`}
      {...props}
    />
  )),
  Action: React.forwardRef(({ className = '', variant = 'primary', ...props }, ref) => {
    const variants = {
      primary: 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500',
      danger: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500'
    };

    return (
      <RadixAlertDialog.Action
        ref={ref}
        className={`inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 ${variants[variant]} ${className}`}
        {...props}
      />
    );
  })
};

// Select Component
const Select = {
  Root: RadixSelect.Root,
  Trigger: React.forwardRef(({ className = '', error = false, ...props }, ref) => {
    const errorClasses = error
      ? 'border-destructive focus:border-destructive focus:ring-destructive/20'
      : 'border-border focus:border-ring focus:ring-ring/20';

    return (
      <RadixSelect.Trigger
        ref={ref}
        className={`inline-flex items-center justify-between w-full px-4 py-3 text-sm bg-background text-foreground border rounded-md focus:outline-none focus:ring-2 focus:ring-offset-1 transition-all duration-300 hover:border-accent-foreground ${errorClasses} ${className}`}
        {...props}
      />
    );
  }),
  Value: RadixSelect.Value,
  Icon: React.forwardRef(({ className = '', ...props }, ref) => (
    <RadixSelect.Icon ref={ref} className={`ml-2 ${className}`} {...props}>
      <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M4.93179 5.43179C4.75605 5.60753 4.75605 5.89245 4.93179 6.06819C5.10753 6.24392 5.39245 6.24392 5.56819 6.06819L7.49999 4.13638L9.43179 6.06819C9.60753 6.24392 9.89245 6.24392 10.0682 6.06819C10.2439 5.89245 10.2439 5.60753 10.0682 5.43179L7.81819 3.18179C7.73379 3.0974 7.61933 3.04999 7.49999 3.04999C7.38064 3.04999 7.26618 3.0974 7.18179 3.18179L4.93179 5.43179ZM10.0682 9.56819C10.2439 9.39245 10.2439 9.10753 10.0682 8.93179C9.89245 8.75606 9.60753 8.75606 9.43179 8.93179L7.49999 10.8636L5.56819 8.93179C5.39245 8.75606 5.10753 8.75606 4.93179 8.93179C4.75605 9.10753 4.75605 9.39245 4.93179 9.56819L7.18179 11.8182C7.26618 11.9026 7.38064 11.95 7.49999 11.95C7.61933 11.95 7.73379 11.9026 7.81819 11.8182L10.0682 9.56819Z" fill="currentColor" fillRule="evenodd" clipRule="evenodd"></path>
      </svg>
    </RadixSelect.Icon>
  )),
  Portal: RadixSelect.Portal,
  Content: React.forwardRef(({ className = '', ...props }, ref) => (
    <RadixSelect.Content
      ref={ref}
      className={`overflow-hidden bg-popover text-popover-foreground rounded-md border border-border shadow-lg z-50 ${className}`}
      {...props}
    />
  )),
  Viewport: React.forwardRef(({ className = '', ...props }, ref) => (
    <RadixSelect.Viewport ref={ref} className={`p-1 ${className}`} {...props} />
  )),
  Item: React.forwardRef(({ className = '', ...props }, ref) => (
    <RadixSelect.Item
      ref={ref}
      className={`relative flex items-center px-3 py-2 text-sm rounded-md cursor-pointer select-none hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground focus:outline-none data-[disabled]:opacity-50 data-[disabled]:pointer-events-none ${className}`}
      {...props}
    />
  )),
  ItemText: RadixSelect.ItemText,
  ItemIndicator: React.forwardRef(({ className = '', ...props }, ref) => (
    <RadixSelect.ItemIndicator ref={ref} className={`absolute right-2 ${className}`} {...props}>
      <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M11.4669 3.72684C11.7558 3.91574 11.8369 4.30308 11.648 4.59198L7.39799 11.092C7.29783 11.2452 7.13556 11.3467 6.95402 11.3699C6.77247 11.3931 6.58989 11.3355 6.45446 11.2124L3.70446 8.71241C3.44905 8.48022 3.43023 8.08494 3.66242 7.82953C3.89461 7.57412 4.28989 7.55529 4.5453 7.78749L6.75292 9.79441L10.6018 3.90792C10.7907 3.61902 11.178 3.53795 11.4669 3.72684Z" fill="currentColor" fillRule="evenodd" clipRule="evenodd"></path>
      </svg>
    </RadixSelect.ItemIndicator>
  )),
  Separator: React.forwardRef(({ className = '', ...props }, ref) => (
    <RadixSelect.Separator ref={ref} className={`h-px bg-gray-200 m-1 ${className}`} {...props} />
  ))
};

// Switch Component
const Switch = React.forwardRef(({ className = '', ...props }, ref) => (
  <RadixSwitch.Root
    ref={ref}
    className={`peer inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:bg-indigo-600 data-[state=unchecked]:bg-gray-200 ${className}`}
    {...props}
  >
    <RadixSwitch.Thumb className="pointer-events-none block h-5 w-5 rounded-full bg-white shadow-lg ring-0 transition-transform data-[state=checked]:translate-x-5 data-[state=unchecked]:translate-x-0" />
  </RadixSwitch.Root>
));

// Slider Component
const Slider = React.forwardRef(({ className = '', ...props }, ref) => (
  <RadixSlider.Root
    ref={ref}
    className={`relative flex w-full touch-none select-none items-center ${className}`}
    {...props}
  >
    <RadixSlider.Track className="relative h-2 w-full grow overflow-hidden rounded-full bg-gray-200">
      <RadixSlider.Range className="absolute h-full bg-indigo-600" />
    </RadixSlider.Track>
    <RadixSlider.Thumb className="block h-5 w-5 rounded-full border-2 border-indigo-600 bg-white ring-offset-white transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50" />
  </RadixSlider.Root>
));

export {
  Container,
  Box,
  Card,
  Input,
  Button,
  Typography,
  Badge,
  Stack,
  Grid,
  Separator,
  Spinner,
  Progress,
  Skeleton,
  Table,
  Tabs,
  StatCard,
  Alert,
  Tooltip,
  InfoIcon,
  Dialog,
  AlertDialog,
  Select,
  Switch,
  Slider,
  SPACING,
  LAYOUT,
  COLORS
};

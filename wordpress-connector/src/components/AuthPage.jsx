import React, { useEffect } from 'react';
import {
  Container, Box, Card, Button, Typography, Grid, Stack, Alert
} from './DesignSystem';

const AuthPage = () => {
  const authUrl = window.surecrmAdmin?.authUrl || '#';
  const pluginsUrl = window.surecrmAdmin?.pluginsUrl || '/wp-admin/plugins.php';
  const hasAuthError = window.surecrmAdmin?.hasAuthError || false;

  useEffect(() => {
    // Add class to body to hide WordPress admin elements
    document.body.classList.add('surecrm-auth-page');

    // Add styles to make the page full width
    const style = document.createElement('style');
    style.id = 'surecrm-auth-styles';
    style.innerHTML = `
      body.surecrm-auth-page #adminmenumain,
      body.surecrm-auth-page #adminmenuback,
      body.surecrm-auth-page #adminmenuwrap,
      body.surecrm-auth-page #wpadminbar {
        display: none !important;
      }
      
      body.surecrm-auth-page #wpcontent,
      body.surecrm-auth-page #wpfooter {
        margin-left: 0 !important;
      }
      
      body.surecrm-auth-page #wpbody-content {
        padding-bottom: 0 !important;
      }
      
      body.surecrm-auth-page .wrap {
        margin: 0 !important;
      }
      
      body.surecrm-auth-page #wpfooter {
        display: none !important;
      }
      
      body.surecrm-auth-page {
        background: var(--background) !important;
      }
      
      body.surecrm-auth-page #wpbody {
        padding-top: 0 !important;
      }
    `;
    document.head.appendChild(style);

    // Cleanup on unmount
    return () => {
      document.body.classList.remove('surecrm-auth-page');
      const authStyles = document.getElementById('surecrm-auth-styles');
      if (authStyles) {
        authStyles.remove();
      }
    };
  }, []);

  return (
    <Box className="min-h-screen bg-background py-12">
      <Container size="xl">
        {/* Error Alert */}
        {hasAuthError && (
          <Box className="max-w-4xl mx-auto mb-6">
            <Alert.Root variant="error">
              <Alert.Title>Authentication failed</Alert.Title>
              <Alert.Description>
                Please try again. If the problem persists, contact support.
              </Alert.Description>
            </Alert.Root>
          </Box>
        )}

        <Card.Root className="shadow-sm max-w-4xl mx-auto rounded-md">
          <Card.Content className="p-0">
            {/* Main content section */}
            <Box className="px-16 py-12 text-center">
              {/* Logo */}
              <Box className="flex justify-center mb-6">
                <img
                  src={`${window.surecrmAdmin?.pluginUrl || ''}assets/images/surecrm-logo.svg`}
                  alt="SureCRM"
                  className="h-16 w-auto"
                />
              </Box>

              {/* Description */}
              <Typography.Body className="text-muted-foreground max-w-2xl mx-auto mb-8 leading-relaxed">
                SureCRM is a comprehensive CRM platform that helps you manage customer relationships and data effectively.
                Connect your WordPress site to centralize customer information, track interactions, and streamline your business operations.
              </Typography.Body>

              {/* Primary CTA */}
              <Button.Root
                variant="primary"
                size="lg"
                asChild
                className="mb-4 !text-white"
              >
                <a href={authUrl}>
                  Get Started Now →
                </a>
              </Button.Root>

              <Box>
                <Button.Root
                  variant="ghost"
                  size="sm"
                  asChild
                >
                  <a href={pluginsUrl}>
                    Go back to the dashboard
                  </a>
                </Button.Root>
              </Box>
            </Box>

            {/* Features Grid */}
            <Box className="px-16 pb-12">
              <Grid cols={2} gap="8">
                <Box className="bg-muted rounded-md p-6 text-center">
                  <Box className="w-14 h-14 bg-primary/10 rounded-md flex items-center justify-center mx-auto mb-4">
                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M14 2L2 8L14 14L26 8L14 2Z" stroke="var(--primary)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                      <path d="M2 20L14 26L26 20" stroke="var(--primary)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                      <path d="M2 14L14 20L26 14" stroke="var(--primary)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                    </svg>
                  </Box>
                  <Typography.H4 className="font-semibold text-foreground mb-2">Customer Management</Typography.H4>
                  <Typography.Body size="sm" className="text-muted-foreground">
                    Centralize and manage all customer data from your WordPress site in one powerful CRM platform.
                  </Typography.Body>
                </Box>

                <Box className="bg-muted rounded-md p-6 text-center">
                  <Box className="w-14 h-14 bg-primary/10 rounded-md flex items-center justify-center mx-auto mb-4">
                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <rect x="3" y="4" width="22" height="18" rx="2" stroke="var(--primary)" strokeWidth="2" />
                      <path d="M3 9H25" stroke="var(--primary)" strokeWidth="2" />
                      <circle cx="10" cy="15" r="1.5" fill="var(--primary)" />
                      <path d="M14 15L16 17L20 13" stroke="var(--primary)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                    </svg>
                  </Box>
                  <Typography.H4 className="font-semibold text-foreground mb-2">Data Synchronization</Typography.H4>
                  <Typography.Body size="sm" className="text-muted-foreground">
                    Automatic daily sync ensures your CRM data stays up-to-date between WordPress and SureCRM platform.
                  </Typography.Body>
                </Box>

                <Box className="bg-muted rounded-md p-6 text-center">
                  <Box className="w-14 h-14 bg-primary/10 rounded-md flex items-center justify-center mx-auto mb-4">
                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <rect x="2" y="2" width="24" height="24" rx="2" stroke="var(--primary)" strokeWidth="2" />
                      <path d="M12 6V12L16 14" stroke="var(--primary)" strokeWidth="2" strokeLinecap="round" />
                      <circle cx="14" cy="14" r="8" stroke="var(--primary)" strokeWidth="2" />
                    </svg>
                  </Box>
                  <Typography.H4 className="font-semibold text-foreground mb-2">Real-time Updates</Typography.H4>
                  <Typography.Body size="sm" className="text-muted-foreground">
                    Get instant updates and notifications when customer data changes or important events occur.
                  </Typography.Body>
                </Box>

                <Box className="bg-muted rounded-md p-6 text-center">
                  <Box className="w-14 h-14 bg-primary/10 rounded-md flex items-center justify-center mx-auto mb-4">
                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <circle cx="14" cy="8" r="4" stroke="var(--primary)" strokeWidth="2" />
                      <path d="M6 24C6 19 9 16 14 16C19 16 22 19 22 24" stroke="var(--primary)" strokeWidth="2" strokeLinecap="round" />
                    </svg>
                  </Box>
                  <Typography.H4 className="font-semibold text-foreground mb-2">Contact Tracking</Typography.H4>
                  <Typography.Body size="sm" className="text-muted-foreground">
                    Track all customer interactions, communications, and touchpoints across your WordPress site.
                  </Typography.Body>
                </Box>

                <Box className="bg-muted rounded-md p-6 text-center">
                  <Box className="w-14 h-14 bg-primary/10 rounded-md flex items-center justify-center mx-auto mb-4">
                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <rect x="4" y="4" width="20" height="20" rx="2" stroke="var(--primary)" strokeWidth="2" />
                      <path d="M4 10H24" stroke="var(--primary)" strokeWidth="2" />
                      <path d="M10 4V10" stroke="var(--primary)" strokeWidth="2" />
                    </svg>
                  </Box>
                  <Typography.H4 className="font-semibold text-foreground mb-2">Secure Connection</Typography.H4>
                  <Typography.Body size="sm" className="text-muted-foreground">
                    OAuth-based authentication ensures your data remains secure with encrypted connections.
                  </Typography.Body>
                </Box>

                <Box className="bg-muted rounded-md p-6 text-center">
                  <Box className="w-14 h-14 bg-primary/10 rounded-md flex items-center justify-center mx-auto mb-4">
                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M6 2L3 6V20C3 21.1 3.89 22 5 22H19C20.1 22 21 21.1 21 20V6L18 2H6Z" stroke="var(--primary)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                      <path d="M3 6H21" stroke="var(--primary)" strokeWidth="2" strokeLinecap="round" />
                      <path d="M16 10C16 11.1 15.1 12 14 12C12.9 12 12 11.1 12 10" stroke="var(--primary)" strokeWidth="2" strokeLinecap="round" />
                    </svg>
                  </Box>
                  <Typography.H4 className="font-semibold text-foreground mb-2">E-commerce Integration</Typography.H4>
                  <Typography.Body size="sm" className="text-muted-foreground">
                    Seamlessly integrate with WooCommerce, SureCart, and Easy Digital Downloads for complete customer insights.
                  </Typography.Body>
                </Box>

                <Box className="bg-muted rounded-md p-6 text-center">
                  <Box className="w-14 h-14 bg-primary/10 rounded-md flex items-center justify-center mx-auto mb-4">
                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <circle cx="9" cy="21" r="1" stroke="var(--primary)" strokeWidth="2" />
                      <circle cx="20" cy="21" r="1" stroke="var(--primary)" strokeWidth="2" />
                      <path d="M1 1H5L7 13H23L25 6H6" stroke="var(--primary)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                    </svg>
                  </Box>
                  <Typography.H4 className="font-semibold text-foreground mb-2">Order Management</Typography.H4>
                  <Typography.Body size="sm" className="text-muted-foreground">
                    Track orders, purchases, and transaction history directly within your CRM platform.
                  </Typography.Body>
                </Box>

                <Box className="bg-muted rounded-md p-6 text-center">
                  <Box className="w-14 h-14 bg-primary/10 rounded-md flex items-center justify-center mx-auto mb-4">
                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <rect x="2" y="5" width="24" height="18" rx="2" stroke="var(--primary)" strokeWidth="2" />
                      <path d="M2 11H26" stroke="var(--primary)" strokeWidth="2" />
                      <circle cx="7" cy="17" r="1" fill="var(--primary)" />
                      <circle cx="14" cy="17" r="1" fill="var(--primary)" />
                      <circle cx="21" cy="17" r="1" fill="var(--primary)" />
                    </svg>
                  </Box>
                  <Typography.H4 className="font-semibold text-foreground mb-2">Custom Workflows</Typography.H4>
                  <Typography.Body size="sm" className="text-muted-foreground">
                    Create automated workflows and triggers based on customer behavior and interactions.
                  </Typography.Body>
                </Box>
              </Grid >
            </Box >

            {/* Brands Section */}
            < Box className="px-16 py-10 bg-muted" >
              <Typography.H3 className="font-semibold text-foreground text-2xl text-center mb-8">
                Trusted by World's Top Brands
              </Typography.H3>

              <Box className="max-w-4xl mx-auto mb-10">
                <img
                  src={`${window.surecrmAdmin?.pluginUrl || ''}assets/images/trusted-brands.png`}
                  alt="Trusted brands including ConvertKit, WhatsApp, Elementor, LearnDash, PayPal, WooCommerce, FluentCRM, SureCart, Google, Slack, Stripe, BuddyBoss, MailChimp, MailerLite, and Zoom"
                  className="w-full h-auto"
                />
              </Box>

              {/* Footer CTA */}
              <Box className="text-center">
                <Button.Root
                  variant="primary"
                  size="lg"
                  asChild
                  className="mb-4 !text-white"
                >
                  <a href={authUrl}>
                    Get Started Now →
                  </a>
                </Button.Root>

                <Box>
                  <Button.Root
                    variant="ghost"
                    size="sm"
                    asChild
                  >
                    <a href={pluginsUrl}>
                      Go back to the dashboard
                    </a>
                  </Button.Root>
                </Box>
              </Box>
            </Box >
          </Card.Content >
        </Card.Root >
      </Container >
    </Box >
  );
};

export default AuthPage;
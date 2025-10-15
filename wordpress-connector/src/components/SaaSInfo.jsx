import React, { useState } from 'react';
import { Container, Box, Card, Button, Typography, Stack, Badge, AlertDialog } from './DesignSystem';
import { useToast } from './ToastContext.jsx';

const SaasInfo = () => {
  const toast = useToast();
  const [disconnecting, setDisconnecting] = useState(false);
  const [confirmOpen, setConfirmOpen] = useState(false);

  const { restUrl, nonce, adminUrl } = window.surecrmAdmin || {};

  const handleDisconnect = async () => {
    if (disconnecting) return;
    setDisconnecting(true);

    try {
      const res = await fetch(`${restUrl}auth/disconnect`, {
        method: 'POST',
        headers: {
          'X-WP-Nonce': nonce,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({})
      });
      const data = await res.json();

      if (!res.ok || !data?.success) {
        throw new Error(data?.message || 'Disconnect failed');
      }

      toast.success('Disconnected', 'You have been disconnected.');
      window.location.href = `${adminUrl}?page=surecrm-dashboard`;
    } catch (e) {
      toast.error('Disconnect Failed', e.message || 'Unable to disconnect');
    } finally {
      setDisconnecting(false);
    }
  };

  return (
    <Container className={"!max-w-3xl"}>
      <Box className="py-4">
        <Card.Root className="rounded-md">

          <Card.Header className="border-0 text-center bg-transparent pb-4">
            <Stack space="3">
              <Box className="flex items-center justify-center gap-3">
                <img
                  src={`${window.surecrmAdmin?.pluginUrl || ''}assets/images/surecrm-logo.svg`}
                  alt="SureCRM"
                  className="h-10 w-auto"
                />
              </Box>

              <Typography.Body size="sm" className="text-muted-foreground max-w-2xl mx-auto">
                Your WordPress site is connected to SureCRM. Manage your CRM data and configurations from your SureCRM dashboard.
              </Typography.Body>
            </Stack>
          </Card.Header>

          <Card.Content className="pt-0">
            <Stack space="4">
              {/* Status */}
              <Box className="bg-muted/30 rounded-md p-4">
                <Box className="flex justify-between items-center">
                  <Box className="flex items-center gap-2">
                    <Typography.Body size="sm" className="text-foreground font-medium">Connection Status</Typography.Body>
                  </Box>
                  <Badge variant="success" size="sm">Connected</Badge>
                </Box>
              </Box>

              {/* Actions */}
              <Box className="flex justify-center">
                <Button.Root
                  asChild
                  size="lg"
                  variant="primary"
                  className="!rounded-md transition-all duration-200 hover:scale-105 hover:shadow-md active:scale-95"
                >
                  <a href="https://app.surecrm.com/" className='hover:text-white' target="_blank" rel="noreferrer">
                    <svg className="mr-2 h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                      <path d="M7 17l9-9M7 7h10v10" />
                    </svg>
                    Open Dashboard
                  </a>
                </Button.Root>
              </Box>

              <AlertDialog.Root open={confirmOpen} onOpenChange={setConfirmOpen}>
                <AlertDialog.Portal>
                  <AlertDialog.Overlay className="fixed inset-0 bg-black/40" />
                  <AlertDialog.Content className="fixed left-1/2 top-1/2 w-[92vw] max-w-md -translate-x-1/2 -translate-y-1/2 rounded-md bg-card p-6 shadow-xl">
                    <AlertDialog.Title className="text-lg font-semibold text-foreground">
                      Disconnect from SureCRM?
                    </AlertDialog.Title>
                    <AlertDialog.Description className="mt-2 text-sm text-muted-foreground">
                      This will remove authentication and cached SureCRM data from WordPress. You can reconnect anytime.
                    </AlertDialog.Description>
                    <AlertDialog.Footer className="mt-6 flex justify-end gap-3">
                      <AlertDialog.Cancel className="rounded-md border border-border px-4 py-2 text-sm font-medium text-muted-foreground hover:bg-muted">
                        Cancel
                      </AlertDialog.Cancel>
                      <AlertDialog.Action
                        onClick={handleDisconnect}
                        disabled={disconnecting}
                        className="rounded-md bg-secondary px-4 py-2 text-sm font-medium text-secondary-foreground hover:bg-secondary/90 disabled:opacity-50"
                      >
                        {disconnecting ? 'Disconnecting…' : 'Disconnect'}
                      </AlertDialog.Action>
                    </AlertDialog.Footer>
                  </AlertDialog.Content>
                </AlertDialog.Portal>
              </AlertDialog.Root>

              <Box className="text-center pt-2">
                <Button.Root
                  size="sm"
                  variant="ghost"
                  className="text-destructive hover:text-destructive/80 text-xs transition-all duration-200 hover:bg-destructive/10 hover:scale-105 active:scale-95 hover:cursor-pointer"
                  onClick={() => setConfirmOpen(true)}
                >
                  Disconnect
                </Button.Root>
              </Box>
            </Stack>
          </Card.Content>
        </Card.Root>
      </Box>
    </Container >
  );
};

export default SaasInfo;
"use client";

import { useState, useEffect } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { Badge } from "@/components/ui/badge";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { DeleteConfirmationDialog } from "@/components/ui/delete-confirmation-dialog";
import { RequiresPermission } from "@/components/requires-permission";
import { getConnectionsAction, deleteConnectionAction, syncConnectionAction } from "@/actions/connections";
import { Globe, Trash2, RefreshCw, RotateCw, Download } from "lucide-react";
import { TooltipChild } from "@/components/ui/tooltip-child";
import { getStatusColor } from "@/lib/utils";

interface Connection {
  id: string;
  integration_name: string;
  site_url: string;
  status: 'active' | 'inactive' | 'error';
  last_sync_at: string | null;
  created_at: string;
  plugin_version?: string;
  permissions?: {
    can_view: boolean;
    can_update: boolean;
    can_delete: boolean;
  };
}

interface ConnectionsPageClientProps {
  initialConnections?: Connection[];
}

export function ConnectionsPageClient({
  initialConnections
}: ConnectionsPageClientProps) {
  const [connections, setConnections] = useState<Connection[]>(initialConnections || []);
  const [isLoading, setIsLoading] = useState(!initialConnections); // Only loading if no initial data provided
  const [deletingId, setDeletingId] = useState<string | null>(null);
  const [syncingId, setSyncingId] = useState<string | null>(null);
  const [deleteDialog, setDeleteDialog] = useState<{
    isOpen: boolean;
    connectionId: string;
    siteName: string;
  }>({
    isOpen: false,
    connectionId: '',
    siteName: '',
  });

  useEffect(() => {
    // Only load connections if no initial data was provided (not even an empty array)
    if (initialConnections === undefined) {
      loadConnections();
    }
  }, [initialConnections]);

  async function loadConnections() {
    try {
      setIsLoading(true);
      const result = await getConnectionsAction();

      if (result.success && result.data) {
        setConnections(result.data);
      } else {
        toast.error("Failed to load connections", {
          description: result.error || "Unable to fetch connections. Please try again.",
        });
      }
    } catch {
      toast.error("Error", {
        description: "An unexpected error occurred while loading connections.",
      });
    } finally {
      setIsLoading(false);
    }
  }

  function handleDeleteClick(connectionId: string, siteName: string) {
    setDeleteDialog({
      isOpen: true,
      connectionId,
      siteName,
    });
  }

  async function handleDeleteConnection() {
    const connectionId = deleteDialog.connectionId;
    try {
      setDeletingId(connectionId);

      const result = await deleteConnectionAction(connectionId);

      if (result.success) {
        toast.success("Connection revoked successfully");
        // Remove the connection from the list
        setConnections(prev => prev.filter(conn => conn.id !== connectionId));
        // Close the dialog
        setDeleteDialog({ isOpen: false, connectionId: '', siteName: '' });
      } else {
        toast.error("Failed to revoke connection", {
          description: result.error || "Unable to revoke connection. Please try again.",
        });
      }
    } catch {
      toast.error("Error", {
        description: "An unexpected error occurred while revoking connection.",
      });
    } finally {
      setDeletingId(null);
    }
  }

  async function handleSyncConnection(connectionId: string, siteName: string) {
    try {
      setSyncingId(connectionId);

      const result = await syncConnectionAction(connectionId);

      if (result.success) {
        // Update last_sync_at timestamp
        setConnections(prev => prev.map(conn =>
          conn.id === connectionId
            ? { ...conn, last_sync_at: new Date().toISOString() }
            : conn
        ));

        toast.success("Sync initiated successfully", {
          description: result.message || `${siteName} sync has been dispatched.`,
        });
      } else {
        toast.error("Sync failed", {
          description: result.error || "Unable to sync connection. Please try again.",
        });
      }
    } catch {
      toast.error("Error", {
        description: "An unexpected error occurred while syncing connection.",
      });
    } finally {
      setSyncingId(null);
    }
  }

  function formatDate(dateString: string | null) {
    if (!dateString) return 'Never';
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  if (isLoading) {
    return (
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <div className="animate-pulse">
            <div className="h-8 bg-muted rounded w-32 mb-2"></div>
            <div className="h-4 bg-muted rounded w-80"></div>
          </div>
          <div className="animate-pulse">
            <div className="h-10 bg-muted rounded w-20"></div>
          </div>
        </div>

        <div className="border rounded-lg">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Site URL</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Plugin Version</TableHead>
                <TableHead>Last Sync</TableHead>
                <TableHead className="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {[1, 2, 3].map((i) => (
                <TableRow key={i}>
                  <TableCell>
                    <Skeleton className="h-4 w-40" />
                  </TableCell>
                  <TableCell>
                    <Skeleton className="h-6 w-16 rounded-full" />
                  </TableCell>
                  <TableCell>
                    <Skeleton className="h-4 w-16" />
                  </TableCell>
                  <TableCell>
                    <Skeleton className="h-4 w-12" />
                  </TableCell>
                  <TableCell>
                    <Skeleton className="h-4 w-24" />
                  </TableCell>
                  <TableCell className="text-right">
                    <div className="flex justify-end gap-2">
                      <Skeleton className="h-8 w-16" />
                      <Skeleton className="h-8 w-16" />
                    </div>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6 max-w-7xl mx-auto">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-foreground">Connections</h1>
          <p className="text-muted-foreground mt-1">
            Manage your website connections and integrations
          </p>
        </div>

        <div className="flex items-center gap-2">
          <Button
            asChild
            size="sm"
            className="flex items-center gap-2 bg-[#005F5A] text-white hover:bg-[#004A46]"
          >
            <a href="/plugin.zip" download>
              <Download className="h-4 w-4" />
              Download Plugin
            </a>
          </Button>

          <Button
            onClick={loadConnections}
            variant="outline"
            size="sm"
            className="flex items-center gap-2"
          >
            <RefreshCw className="h-4 w-4" />
            Refresh
          </Button>
        </div>
      </div>

      {connections.length === 0 ? (
        <Card className="text-center py-12">
          <CardHeader>
            <div className="mx-auto w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-4">
              <Globe className="h-6 w-6 text-gray-400" />
            </div>
            <CardTitle className="text-xl">No connections found</CardTitle>
            <CardDescription>
              You haven&apos;t connected any external integrations yet. Connect your first integration to get started.
            </CardDescription>
          </CardHeader>
        </Card>
      ) : (
        <div className="border rounded-lg">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Site URL</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Plugin Version</TableHead>
                <TableHead>Last Sync</TableHead>
                <TableHead className="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {connections.map((connection) => (
                <TableRow key={connection.id}>
                  <TableCell>
                    <div className="text-sm">
                      <div className="font-medium text-foreground">{connection.site_url}</div>
                      <div className="text-xs text-muted-foreground">
                        Connected: {formatDate(connection.created_at)}
                      </div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Badge
                      variant="outline"
                      className={getStatusColor(connection.status)}
                    >
                      {connection.status.charAt(0).toUpperCase() + connection.status.slice(1)}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div className="text-sm text-muted-foreground">
                      {connection.plugin_version || 'N/A'}
                    </div>
                  </TableCell>
                  <TableCell>
                    <div className="text-sm text-muted-foreground">
                      {formatDate(connection.last_sync_at)}
                    </div>
                  </TableCell>
                  <TableCell className="text-right">
                    <div className="flex justify-end gap-2">
                      <TooltipChild content="Sync">
                        <Button
                          variant="outline"
                          size="sm"
                          disabled={syncingId === connection.id}
                          className="text-[#005F5A] hover:text-white hover:bg-[#005F5A] "
                          onClick={() => handleSyncConnection(connection.id, connection.site_url)}
                        >
                          {syncingId === connection.id ? (
                            <RefreshCw className="h-4 w-4 animate-spin" />
                          ) : (
                            <RotateCw className="h-4 w-4" />
                          )}
                          {/* Sync */}
                        </Button>
                      </TooltipChild>
                      <RequiresPermission resource={connection} resourcePermission="can_delete">
                        <TooltipChild content="Revoke">

                          <Button
                            variant="outline"
                            size="sm"
                            disabled={deletingId === connection.id}
                            className="text-red-600 hover:text-red-700 hover:bg-red-50"
                            onClick={() => handleDeleteClick(connection.id, connection.site_url)}
                          >
                            {deletingId === connection.id ? (
                              <RefreshCw className="h-4 w-4 animate-spin" />
                            ) : (
                              <Trash2 className="h-4 w-4" />
                            )}
                            {/* Revoke */}
                          </Button>
                        </TooltipChild>

                      </RequiresPermission>
                    </div>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>
      )}

      <DeleteConfirmationDialog
        open={deleteDialog.isOpen}
        onOpenChange={(open) =>
          setDeleteDialog(prev => ({ ...prev, isOpen: open }))
        }
        title="Revoke Connection"
        description={`Are you sure you want to revoke the connection to ${deleteDialog.siteName}? This action cannot be undone.`}
        confirmText="Revoke Connection"
        onConfirm={handleDeleteConnection}
        isLoading={deletingId === deleteDialog.connectionId}
      />
    </div>
  );
}
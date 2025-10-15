import { Metadata } from "next";
import { ConnectionsPageClient } from "./_components/connections-page-client";
import { getConnectionsAction } from "@/actions/connections";

export const metadata: Metadata = {
  title: `Connections | ${process.env.NEXT_PUBLIC_APP_NAME}`,
  description: "Manage your website connections",
};

export default async function ConnectionsPage() {
  // Fetch data on server to eliminate client loading states and blink
  const connectionsResult = await getConnectionsAction();

  return (
    <ConnectionsPageClient
      initialConnections={connectionsResult.success ? connectionsResult.data : []}
    />
  );
}
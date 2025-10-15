import { Metadata } from "next";
import { ConnectPageClient } from "./_components/connect-page-client";

export const metadata: Metadata = {
  title: `Connect WordPress | ${process.env.NEXT_PUBLIC_APP_NAME}`,
  description: `Connect your WordPress site with our ${process.env.NEXT_PUBLIC_APP_NAME} Platform`,
};

export default function ConnectPage() {
  return <ConnectPageClient />;
}
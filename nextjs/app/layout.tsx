import type { Metadata } from "next";
import { Figtree } from "next/font/google";
import Script from "next/script";
import { Toaster } from "@/components/ui/sonner";
import "./globals.css";

const figtree = Figtree({
  variable: "--font-figtree",
  subsets: ["latin"],
  weight: ["300", "400", "500", "600", "700", "800", "900"],
});

export const metadata: Metadata = {
  title: `${process.env.NEXT_PUBLIC_APP_NAME} turns unsure into sure.`,
  description: `${process.env.NEXT_PUBLIC_APP_NAME} turns unsure into sure by showing you exactly what works - powered by real data, not guesswork`,
  icons: {
    icon: [
      {
        url: '/icon.svg',
        type: 'image/svg+xml',
      },
    ],
    apple: [
      {
        url: '/apple-icon.svg',
        type: 'image/svg+xml',
      },
    ],
  },
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en">
      <body
        className={`${figtree.variable} font-sans antialiased`}
      >
        <Script
          id="surecart-config"
          strategy="beforeInteractive"
          dangerouslySetInnerHTML={{
            __html: `window.SureCartAffiliatesConfig = {"publicToken":"pt_Yq2qgtPqLNZ9e9V92RFyLeZv"};`,
          }}
        />
        <Script
          src="https://js.surecart.com/v1/affiliates"
          strategy="afterInteractive"
        />
        {children}
        <Toaster richColors position="top-right" />
      </body>
    </html>
  );
}

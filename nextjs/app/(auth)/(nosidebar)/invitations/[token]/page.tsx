import { Suspense } from "react";
import { InvitationPreviewClient } from "../_components/invitation-preview-client";
import { Card, CardContent } from "@/components/ui/card";

interface InvitationPageProps {
  params: Promise<{
    token: string;
  }>;
}

export default async function InvitationPage({ params }: InvitationPageProps) {
  const { token } = await params;

  return (
    <div className="min-h-screen bg-background flex items-center justify-center p-4">
      <div className="w-full max-w-md">
        <Suspense fallback={
          <Card className="shadow-none">
            <CardContent className="flex items-center justify-center py-12">
              <div className="text-center">
                <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-primary mx-auto"></div>
                <p className="mt-3 text-muted-foreground text-sm">Loading invitation...</p>
              </div>
            </CardContent>
          </Card>
        }>
          <InvitationPreviewClient token={token} />
        </Suspense>
      </div>
    </div>
  );
}
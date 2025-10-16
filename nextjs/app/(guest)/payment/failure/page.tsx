"use client";

import { useSearchParams } from "next/navigation";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { XCircle } from "lucide-react";
import Link from "next/link";

export default function PaymentFailurePage() {
  const searchParams = useSearchParams();
  const paymentUuid = searchParams.get("payment_uuid");

  return (
    <div className="container max-w-2xl mx-auto py-16 px-4">
      <Card>
        <CardHeader>
          <CardTitle className="text-center">Payment Failed</CardTitle>
        </CardHeader>
        <CardContent className="text-center space-y-6">
          <XCircle className="w-16 h-16 mx-auto text-red-500" />

          <div>
            <p className="text-lg font-medium mb-2">
              Your payment could not be completed
            </p>
            <p className="text-muted-foreground">
              The payment was cancelled or failed. Please try again.
            </p>
            {paymentUuid && (
              <p className="text-xs text-muted-foreground mt-2">
                Payment ID: {paymentUuid}
              </p>
            )}
          </div>

          <div className="flex gap-4 justify-center">
            <Button asChild variant="outline">
              <Link href="/pricing">View Plans</Link>
            </Button>
            <Button asChild>
              <Link href="/dashboard">Go to Dashboard</Link>
            </Button>
          </div>

          <div className="text-sm text-muted-foreground">
            If you continue to experience issues, please contact our support team.
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

"use client";

import { useEffect } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { Loader2 } from "lucide-react";

/**
 * Generic return page for payment gateways like Khalti
 * This page receives the callback and redirects to success/failure
 */
export default function PaymentReturnPage() {
  const router = useRouter();
  const searchParams = useSearchParams();

  useEffect(() => {
    const paymentUuid = searchParams.get("payment_uuid");
    const status = searchParams.get("status");
    const pidx = searchParams.get("pidx");
    const transactionId = searchParams.get("transaction_id");

    if (!paymentUuid) {
      router.push("/payment/failure");
      return;
    }

    // Build query string with all parameters
    const params = new URLSearchParams();
    params.set("payment_uuid", paymentUuid);

    if (pidx) params.set("pidx", pidx);
    if (transactionId) params.set("transaction_id", transactionId);
    if (status) params.set("status", status);

    // Add any other params from the URL
    searchParams.forEach((value, key) => {
      if (!["payment_uuid", "pidx", "transaction_id", "status"].includes(key)) {
        params.set(key, value);
      }
    });

    // Check status to determine redirect
    if (status === "Completed" || status === "completed" || status === "success") {
      router.push(`/payment/success?${params.toString()}`);
    } else if (status === "failed" || status === "cancelled") {
      router.push(`/payment/failure?${params.toString()}`);
    } else {
      // Default to success page for verification
      router.push(`/payment/success?${params.toString()}`);
    }
  }, [searchParams, router]);

  return (
    <div className="container max-w-2xl mx-auto py-16 px-4 text-center">
      <Loader2 className="w-16 h-16 mx-auto animate-spin text-primary mb-4" />
      <p className="text-muted-foreground">Processing your payment...</p>
    </div>
  );
}

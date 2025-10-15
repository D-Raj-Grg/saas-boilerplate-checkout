"use client";

import { useEffect } from "react";
import { Button } from "@/components/ui/button";
import { AlertCircle } from "lucide-react";
import Link from "next/link";

export default function GuestError({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  useEffect(() => {
  }, [error]);

  return (
    <div className="min-h-screen flex items-center justify-center p-4">
      <div className="w-full max-w-md text-center space-y-6">
        <div className="flex justify-center">
          <AlertCircle className="h-12 w-12 text-destructive" />
        </div>
        
        <div className="space-y-2">
          <h2 className="text-2xl font-semibold text-foreground">
            Oops! Something went wrong
          </h2>
          <p className="text-muted-foreground">
            We encountered an error while loading this page.
          </p>
        </div>
        
        <div className="flex flex-col gap-3">
          <Button
            onClick={() => reset()}
            className="w-full bg-primary text-primary-foreground hover:bg-primary/90"
          >
            Try again
          </Button>
          
          <Link href="/" className="text-sm text-muted-foreground hover:text-foreground">
            Return to homepage
          </Link>
        </div>
      </div>
    </div>
  );
}
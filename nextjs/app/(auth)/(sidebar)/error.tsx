"use client";

import { useEffect } from "react";
import { Button } from "@/components/ui/button";
import { AlertTriangle } from "lucide-react";

export default function Error({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  useEffect(() => {
    // Log the error to an error reporting service
  }, [error]);

  return (
    <div className="flex flex-col items-center justify-center min-h-[60vh] p-6">
      <div className="text-center space-y-4 max-w-md">
        <div className="flex justify-center">
          <AlertTriangle className="h-12 w-12 text-red-600" />
        </div>

        <h2 className="text-2xl font-semibold text-gray-900">
          Something went wrong
        </h2>

        <p className="text-gray-600">
          {error.message || "An unexpected error occurred. Please try again."}
        </p>

        <div className="pt-4">
          <Button
            onClick={() => reset()}
            className=""
          >
            Try again
          </Button>
        </div>
      </div>
    </div>
  );
}
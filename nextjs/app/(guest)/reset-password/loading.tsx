import { Skeleton } from "@/components/ui/skeleton";
import { Card, CardContent } from "@/components/ui/card";

export default function ResetPasswordLoading() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-background">
      <div className="mx-auto w-full max-w-sm">
        <Card className="shadow-2xl">
          <CardContent className="p-8 space-y-6">
          {/* Header */}
          <div className="space-y-2">
            <Skeleton className="h-7 w-40" /> {/* Reset Password */}
            <Skeleton className="h-4 w-56" /> {/* Description */}
          </div>

          {/* Form */}
          <div className="space-y-4">
            <div className="space-y-2">
              <Skeleton className="h-4 w-24" /> {/* New Password label */}
              <Skeleton className="h-10 w-full" /> {/* Password input */}
            </div>
            <div className="space-y-2">
              <Skeleton className="h-4 w-32" /> {/* Confirm Password label */}
              <Skeleton className="h-10 w-full" /> {/* Confirm Password input */}
            </div>
            <Skeleton className="h-10 w-full" /> {/* Reset Password button */}
          </div>

          {/* Footer */}
          <div className="text-center">
            <Skeleton className="h-4 w-32 mx-auto" /> {/* Back to login */}
          </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
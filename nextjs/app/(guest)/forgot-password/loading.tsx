import { Skeleton } from "@/components/ui/skeleton";
import { Card, CardContent } from "@/components/ui/card";

export default function ForgotPasswordLoading() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-background">
      <div className="mx-auto w-full max-w-sm">
        <Card className="shadow-2xl">
          <CardContent className="p-8 space-y-6">
          {/* Header */}
          <div className="space-y-2">
            <Skeleton className="h-7 w-48" /> {/* Forgot Password? */}
            <Skeleton className="h-4 w-64" /> {/* Description */}
          </div>

          {/* Form */}
          <div className="space-y-4">
            <div className="space-y-2">
              <Skeleton className="h-4 w-12" /> {/* Email label */}
              <Skeleton className="h-10 w-full" /> {/* Email input */}
            </div>
            <Skeleton className="h-10 w-full" /> {/* Send Reset Link button */}
          </div>

          {/* Footer */}
          <div className="text-center space-y-2">
            <Skeleton className="h-4 w-32 mx-auto" /> {/* Back to login */}
          </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
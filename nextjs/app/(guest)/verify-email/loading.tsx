import { Skeleton } from "@/components/ui/skeleton";
import { Card, CardContent } from "@/components/ui/card";

export default function VerifyEmailLoading() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-background px-4">
      <div className="mx-auto w-full max-w-sm">
        <Card className="shadow-2xl">
          <CardContent className="p-8 space-y-6">
          {/* Header */}
          <div className="space-y-2 text-center">
            <Skeleton className="h-7 w-32 mx-auto" /> {/* Verify Email */}
            <Skeleton className="h-4 w-64 mx-auto" /> {/* Description */}
          </div>

          {/* Status Content */}
          <div className="space-y-4">
            <div className="text-center space-y-3">
              <Skeleton className="h-12 w-12 rounded-full mx-auto" /> {/* Icon placeholder */}
              <Skeleton className="h-5 w-48 mx-auto" /> {/* Status message */}
              <Skeleton className="h-4 w-56 mx-auto" /> {/* Sub message */}
            </div>
            
            <Skeleton className="h-10 w-full" /> {/* Action button */}
          </div>

          {/* Footer */}
          <div className="text-center space-y-2">
            <Skeleton className="h-4 w-36 mx-auto" /> {/* Additional info */}
            <Skeleton className="h-4 w-28 mx-auto" /> {/* Back to login */}
          </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
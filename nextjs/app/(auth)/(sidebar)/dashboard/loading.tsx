import { Skeleton } from "@/components/ui/skeleton";
import { Card, CardContent, CardHeader } from "@/components/ui/card";

export default function DashboardLoading() {
  return (
    <div className="space-y-6 max-w-7xl mx-auto">
      {/* Page Header */}
      <div className="flex items-center justify-between">
        <div className="space-y-2">
          <Skeleton className="h-8 w-32" />
          <Skeleton className="h-4 w-96" />
        </div>
        <Skeleton className="h-10 w-36" />
      </div>

      {/* Stats cards */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        {[...Array(4)].map((_, i) => (
          <Card key={i}>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <Skeleton className="h-4 w-24" />
              <Skeleton className="h-10 w-10 rounded" />
            </CardHeader>
            <CardContent>
              <Skeleton className="h-8 w-16" />
              <Skeleton className="h-3 w-32 mt-1" />
            </CardContent>
          </Card>
        ))}
      </div>

      <Card className="gap-4">
        <CardHeader>
          <div className="flex items-center justify-between">
            <div>
              <div className="flex items-center gap-2 mb-2">
                <Skeleton className="h-5 w-5" />
                <Skeleton className="h-6 w-32" />
              </div>
            </div>
            <Skeleton className="h-8 w-20" />
          </div>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {[...Array(3)].map((_, i) => (
              <div
                key={i}
                className="flex items-center justify-between p-4 rounded-lg border"
              >
                <div className="flex-1 space-y-2">
                  <div className="flex items-center gap-3">
                    <Skeleton className="h-5 w-48" />
                  </div>
                  <div className="flex items-center gap-4 text-sm">
                    <div className="flex items-center gap-1">
                      <Skeleton className="h-3 w-3" />
                      <Skeleton className="h-4 w-16" />
                    </div>
                    <div className="grid grid-cols-2 gap-2 max-w-sm">
                      {[...Array(2)].map((_, j) => (
                        <Skeleton key={j} className="h-6 w-20 rounded-full" />
                      ))}
                    </div>
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <Skeleton className="h-8 w-20" />
                  <Skeleton className="h-8 w-16" />
                  <Skeleton className="h-8 w-16" />
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* Account Information Section */}
      <Card>
        <CardHeader>
          <Skeleton className="h-6 w-40" />
          <Skeleton className="h-4 w-64" />
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            <div className="flex items-center justify-between p-4 rounded-md border">
              <div className="flex-1 space-y-2">
                <Skeleton className="h-4 w-24" />
                <div className="flex items-center gap-4">
                  <Skeleton className="h-4 w-32" />
                  <Skeleton className="h-6 w-16 rounded-full" />
                </div>
              </div>
            </div>
            <div className="flex items-center justify-between p-4 rounded-md border">
              <div className="flex-1 space-y-2">
                <Skeleton className="h-4 w-20" />
                <div className="flex items-center gap-4">
                  <Skeleton className="h-4 w-28" />
                  <Skeleton className="h-6 w-12 rounded-full" />
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
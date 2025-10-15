export default function ProfileLoading() {
  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <div className="h-8 w-32 bg-muted rounded animate-pulse mb-2" />
          <div className="h-4 w-48 bg-muted/50 rounded animate-pulse" />
        </div>
      </div>

      <div className="flex gap-8">
        {/* Sidebar Skeleton */}
        <div className="max-w-80 flex-shrink-0 space-y-1">
          <div className="h-12 bg-card border rounded-lg animate-pulse" />
          <div className="h-12 bg-card border rounded-lg animate-pulse" />
        </div>

        {/* Content Skeleton */}
        <div className="flex-1 space-y-6">
          <div className="bg-card border rounded-lg p-6 space-y-4">
            <div className="flex items-center gap-3">
              <div className="h-9 w-9 bg-muted rounded-md animate-pulse" />
              <div>
                <div className="h-6 w-40 bg-muted rounded animate-pulse mb-2" />
                <div className="h-4 w-64 bg-muted/50 rounded animate-pulse" />
              </div>
            </div>

            <div className="space-y-4">
              <div className="space-y-2">
                <div className="h-4 w-20 bg-muted rounded animate-pulse" />
                <div className="h-10 bg-muted rounded animate-pulse" />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <div className="h-4 w-24 bg-muted rounded animate-pulse" />
                  <div className="h-10 bg-muted rounded animate-pulse" />
                </div>
                <div className="space-y-2">
                  <div className="h-4 w-20 bg-muted rounded animate-pulse" />
                  <div className="h-10 bg-muted rounded animate-pulse" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
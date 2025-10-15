import { Skeleton } from "@/components/ui/skeleton";

export default function SettingsLoading() {
  return (
    <div className="max-w-7xl mx-auto space-y-6">
      {/* Page Header */}
      <div className="flex items-center justify-between">
        <div className="space-y-2">
          <Skeleton className="h-8 w-32" />
          <Skeleton className="h-4 w-96" />
        </div>
      </div>

      {/* Settings form */}
      <div className="space-y-6">
        <div className="border border-gray-200 rounded-lg">
          <div className="pb-4 p-6 border-b">
            <Skeleton className="h-6 w-48" />
            <Skeleton className="h-4 w-80 mt-2" />
          </div>
          <div className="p-6 space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Skeleton className="h-4 w-48" />
                <Skeleton className="h-10 w-full" />
                <Skeleton className="h-3 w-64" />
              </div>
              <div className="space-y-2">
                <Skeleton className="h-4 w-48" />
                <Skeleton className="h-10 w-full" />
                <Skeleton className="h-3 w-64" />
              </div>
            </div>
          </div>
        </div>

        <div className="border border-gray-200 rounded-lg">
          <div className="pb-4 p-6 border-b">
            <Skeleton className="h-6 w-56" />
            <Skeleton className="h-4 w-96 mt-2" />
          </div>
          <div className="p-6 space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Skeleton className="h-4 w-40" />
                <Skeleton className="h-10 w-full" />
                <Skeleton className="h-3 w-56" />
              </div>
              <div className="space-y-2">
                <Skeleton className="h-4 w-32" />
                <Skeleton className="h-10 w-full" />
                <Skeleton className="h-3 w-64" />
              </div>
            </div>
          </div>
        </div>


        {/* GDPR Compliance Card */}
        <div className="border border-gray-200 rounded-lg">
          <div className="pb-4 p-6 border-b">
            <Skeleton className="h-6 w-36" />
            <Skeleton className="h-4 w-88 mt-2" />
          </div>
          <div className="p-6 space-y-4">
            <div className="flex items-center justify-between p-3 rounded-md border border-gray-100">
              <div className="space-y-0.5">
                <Skeleton className="h-4 w-32" />
                <Skeleton className="h-3 w-64" />
              </div>
              <Skeleton className="h-6 w-11 rounded-full" />
            </div>

            {/* Conditional GDPR fields */}
            <div className="space-y-4 pt-2">
              <div className="space-y-2">
                <Skeleton className="h-4 w-36" />
                <Skeleton className="h-16 w-full" />
              </div>
              <div className="space-y-2">
                <Skeleton className="h-4 w-48" />
                <Skeleton className="h-10 w-full" />
                <Skeleton className="h-3 w-72" />
              </div>
            </div>
          </div>
        </div>

        {/* Submit Button */}
        <div className="flex justify-end">
          <Skeleton className="h-10 w-32" />
        </div>
      </div>
    </div>
  );
}
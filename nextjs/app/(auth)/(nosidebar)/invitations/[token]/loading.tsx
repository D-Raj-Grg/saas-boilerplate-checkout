import { Skeleton } from "@/components/ui/skeleton";

export default function InvitationLoading() {
  return (
    <div className="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-md w-full space-y-6">
        {/* Header */}
        <div className="text-center">
          <div className="flex justify-center mb-4">
            <div className="bg-blue-100 p-3 rounded-full">
              <Skeleton className="h-8 w-8" />
            </div>
          </div>
          <Skeleton className="h-8 w-48 mx-auto" />
          <Skeleton className="h-4 w-72 mx-auto mt-2" />
        </div>

        {/* Invitation Details Card */}
        <div className="bg-white shadow rounded-lg">
          <div className="p-6 border-b">
            <div className="flex items-center gap-2">
              <Skeleton className="h-5 w-5" />
              <Skeleton className="h-6 w-48" />
            </div>
            <Skeleton className="h-4 w-24 mt-1" />
          </div>
          <div className="p-6 space-y-4">
            <div className="flex items-center gap-3">
              <Skeleton className="h-5 w-5" />
              <div>
                <Skeleton className="h-4 w-32" />
                <Skeleton className="h-3 w-20 mt-1" />
              </div>
            </div>

            <div className="flex items-center gap-3">
              <Skeleton className="h-5 w-5" />
              <div>
                <div className="flex items-center gap-2">
                  <Skeleton className="h-4 w-8" />
                  <Skeleton className="h-6 w-16 rounded-full" />
                </div>
              </div>
            </div>

            <div className="bg-gray-50 p-3 rounded-md">
              <Skeleton className="h-4 w-full" />
              <Skeleton className="h-4 w-3/4 mt-1" />
            </div>

            <div className="flex items-center gap-3">
              <Skeleton className="h-4 w-4" />
              <Skeleton className="h-4 w-40" />
            </div>
          </div>
        </div>

        {/* Actions Card */}
        <div className="bg-white shadow rounded-lg">
          <div className="p-6">
            <div className="space-y-4">
              <div className="text-center">
                <Skeleton className="h-4 w-64 mx-auto mb-4" />
              </div>
              <div className="grid grid-cols-1 gap-3">
                <Skeleton className="h-12 w-full" />
                <Skeleton className="h-12 w-full" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
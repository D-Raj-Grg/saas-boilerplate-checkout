import { Skeleton } from "@/components/ui/skeleton";
import { Star } from "lucide-react";
import { cn } from "@/lib/utils";

export default function OrganizationsLoading() {
  return (
    <div className="min-h-screen grid grid-cols-1 lg:grid-cols-[45%_55%]">
      {/* Left Panel - Testimonial (45% width, hidden on mobile) */}
      <div className="hidden lg:flex bg-gray-50 sticky top-0 h-screen overflow-y-auto justify-end">
        <div className="flex flex-col pt-20 p-8 w-full max-w-md">
          <div>
            {/* Logo */}
            <div className="mb-8">
              <Skeleton className="h-10 w-32" />
            </div>

            <div className="border p-10 rounded-lg shadow bg-white">
              {/* Testimonial Content */}
              <div className="mb-6">
                <Skeleton className="h-6 w-48 mb-4" />
                <div className="space-y-2">
                  <Skeleton className="h-4 w-full" />
                  <Skeleton className="h-4 w-full" />
                  <Skeleton className="h-4 w-3/4" />
                </div>
              </div>

              {/* Author */}
              <div className="flex items-center gap-4">
                <Skeleton className="w-12 h-12 rounded-full" />
                <div className="space-y-2 flex-1">
                  <div className="flex items-center gap-1">
                    {Array.from({ length: 5 }).map((_, index) => (
                      <Star
                        key={index}
                        className="w-4 h-4 text-gray-300"
                      />
                    ))}
                  </div>
                  <Skeleton className="h-4 w-32" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Right Panel - Organizations List (55% width) */}
      <div className="bg-white sticky top-0 h-screen overflow-y-auto">
        <div className="flex pt-20 min-h-full p-10 justify-start">
          <div className="max-w-xl w-full">
            {/* Header */}
            <div className="text-left mb-8">
              <Skeleton className="h-8 w-64 mb-2" />
              <Skeleton className="h-5 w-96 max-w-full" />
            </div>

            {/* Organizations List - Bordered container with no spacing */}
            <div className="mb-6 border border-[#E5E5E5] rounded-lg overflow-hidden">
              {[...Array(3)].map((_, index) => (
                <div
                  key={index}
                  className={cn(
                    "p-4 bg-white",
                    index !== 2 && "border-b border-[#E5E5E5]"
                  )}
                >
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <Skeleton className="h-5 w-48 mb-2" />
                      <Skeleton className="h-4 w-64" />
                    </div>
                  </div>
                </div>
              ))}
            </div>

            {/* Create New Organization Button */}
            <Skeleton className="h-12 w-full mb-6" />

            {/* Footer */}
            <div className="pt-6 text-left space-y-2">
              <div className="flex items-center gap-1">
                <Skeleton className="h-4 w-32" />
                <Skeleton className="h-4 w-48" />
                <Skeleton className="h-4 w-16" />
              </div>
              <div className="flex items-center gap-1">
                <Skeleton className="h-4 w-56" />
                <Skeleton className="h-4 w-24" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
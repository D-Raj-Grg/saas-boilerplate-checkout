import { Skeleton } from "@/components/ui/skeleton";

export default function LoginLoading() {
  return (
    <div className="flex h-screen">
      {/* Left side - Form Loading */}
      <div className="flex w-full items-center justify-center bg-background lg:w-1/2">
        <div className="mx-auto w-full max-w-sm space-y-6">
          {/* Card Header */}
          <div className="bg-white p-8 rounded-md shadow-2xl space-y-6">
            <div className="space-y-2">
              <Skeleton className="h-8 w-48" /> {/* Welcome Back */}
              <Skeleton className="h-4 w-64" /> {/* Description */}
            </div>

            {/* Form Fields */}
            <div className="space-y-4">
              <div className="space-y-2">
                <Skeleton className="h-4 w-12" /> {/* Email label */}
                <Skeleton className="h-10 w-full" /> {/* Email input */}
              </div>
              <div className="space-y-2">
                <div className="flex justify-between">
                  <Skeleton className="h-4 w-16" /> {/* Password label */}
                  <Skeleton className="h-4 w-24" /> {/* Forgot password */}
                </div>
                <Skeleton className="h-10 w-full" /> {/* Password input */}
              </div>
              <Skeleton className="h-10 w-full" /> {/* Sign In button */}
            </div>

            {/* Footer */}
            <div className="text-center">
              <Skeleton className="h-4 w-48 mx-auto" /> {/* Don't have account */}
            </div>
          </div>
        </div>
      </div>

      {/* Right side - Promotional Content Loading */}
      <div className="hidden lg:block lg:w-1/2 relative">
        {/* Background gradient */}
        <div className="absolute inset-0 bg-primary/90" />
        
        {/* Content */}
        <div className="relative z-10 flex h-full flex-col items-center justify-center p-12 text-white">
          <div className="space-y-6 text-center">
            {/* Main heading */}
            <Skeleton className="h-12 w-80 bg-white/20 mx-auto" />
            <Skeleton className="h-12 w-60 bg-white/20 mx-auto" />
            
            {/* Description */}
            <Skeleton className="h-6 w-96 bg-white/15 mx-auto" />
            <Skeleton className="h-6 w-80 bg-white/15 mx-auto" />
            
            {/* Stats */}
            <div className="flex gap-8 pt-6">
              <div className="text-center space-y-2">
                <Skeleton className="h-10 w-16 bg-white/20 mx-auto" />
                <Skeleton className="h-4 w-20 bg-white/15 mx-auto" />
              </div>
              <div className="text-center space-y-2">
                <Skeleton className="h-10 w-16 bg-white/20 mx-auto" />
                <Skeleton className="h-4 w-20 bg-white/15 mx-auto" />
              </div>
              <div className="text-center space-y-2">
                <Skeleton className="h-10 w-16 bg-white/20 mx-auto" />
                <Skeleton className="h-4 w-20 bg-white/15 mx-auto" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
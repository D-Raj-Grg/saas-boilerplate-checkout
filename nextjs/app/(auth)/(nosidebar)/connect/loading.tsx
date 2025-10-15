import { Skeleton } from "@/components/ui/skeleton";

export default function ConnectLoading() {
  return (
    <div className="flex flex-col justify-center items-center w-full h-full pt-10">
      <div className="w-[550px] max-w-[800px] mx-auto bg-white shadow-sm rounded-md p-10 text-center">
        {/* Title and description */}
        <Skeleton className="h-8 w-48 mx-auto mb-3" />
        <Skeleton className="h-4 w-96 mx-auto mb-10" />

        {/* Connection animation between logos */}
        <div className="grid grid-cols-3 items-center mb-[30px]">
          <div className="justify-self-end">
            <Skeleton className="w-12 h-12 rounded mx-auto" />
          </div>
          <div className="relative">
            <Skeleton className="h-8 w-8 rounded-full absolute -bottom-4 left-1/2 transform -translate-x-1/2" />
            <div className="border-dashed border-t"></div>
          </div>
          <div className="justify-self-start">
            <Skeleton className="w-12 h-12 rounded mx-auto" />
          </div>
        </div>

        {/* Permission text */}
        <div className="text-sm mt-5 space-y-1">
          <Skeleton className="h-4 w-32 mx-auto" />
          <Skeleton className="h-4 w-48 mx-auto" />
          <Skeleton className="h-4 w-64 mx-auto" />
        </div>

        {/* Workspace selector */}
        <div className="my-4 text-center">
          <div className="flex justify-center items-center w-full relative">
            <Skeleton className="h-10 w-60" />
          </div>
        </div>

        {/* Connect button */}
        <div className="flex items-center mb-5 text-center justify-center w-full">
          <Skeleton className="h-10 w-60" />
        </div>

        {/* Account switch option */}
        <div className="text-center space-y-1">
          <Skeleton className="h-4 w-32 mx-auto" />
          <Skeleton className="h-4 w-40 mx-auto" />
        </div>

        {/* Terms and privacy */}
        <div className="text-sm mt-5 space-y-1">
          <Skeleton className="h-4 w-48 mx-auto" />
          <Skeleton className="h-4 w-40 mx-auto" />
        </div>
      </div>
    </div>
  );
}
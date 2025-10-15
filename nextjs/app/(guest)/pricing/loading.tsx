import { Skeleton } from "@/components/ui/skeleton";
import { cn } from "@/lib/utils";

export default function PricingLoading() {
  return (
    <div className={cn(
      'w-full overflow-x-hidden xl:overflow-x-visible relative',
    )}>
      {/* Animated Header Background */}
      <div className="w-full h-[1120px] absolute overflow-hidden top-0 left-0 bg-gradient-to-b from-teal-50 via-white to-transparent">
        <div className="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-white" />
      </div>

      {/* Header */}
      <div className="w-full pt-10 flex justify-center relative">
        <div className="w-[80vw] max-sm:w-[90vw] pb-[48px] relative z-10">
          <div className="flex items-center justify-between mb-6">
            <Skeleton className="h-10 w-32" />
            <Skeleton className="h-10 w-24" />
          </div>

          {/* Headline */}
          <div className="text-center space-y-4">
            <Skeleton className="h-12 w-96 mx-auto" />
            <Skeleton className="h-6 w-[600px] max-w-full mx-auto" />
          </div>
        </div>
      </div>

      {/* Pricing Slabs */}
      <div className="max-sm:px-4 max-tablet:px-6 w-full">
        <div className="relative w-full mb-10 mx-auto">
          <div className="flex flex-wrap justify-center gap-8 max-w-7xl mx-auto">
            {[...Array(3)].map((_, i) => (
              <div
                key={i}
                className="w-[400px] max-sm:max-w-[400px] max-tablet:w-full"
              >
                <div className={cn(
                  "bg-white border rounded-lg p-8 shadow-sm",
                  i === 1 && "border-primary/50 shadow-lg scale-105"
                )}>
                  {/* Special Tag for Middle Card */}
                  {i === 1 && (
                    <div className="mb-4">
                      <Skeleton className="h-6 w-32 mx-auto" />
                    </div>
                  )}

                  {/* Plan Name & Price */}
                  <div className="space-y-3 mb-6 text-center">
                    <Skeleton className="h-6 w-32 mx-auto" />
                    <Skeleton className="h-10 w-40 mx-auto" />
                    <Skeleton className="h-4 w-full" />
                  </div>

                  {/* CTA Button */}
                  <Skeleton className="h-12 w-full mb-6" />

                  {/* Features List */}
                  <div className="space-y-3">
                    {[...Array(8)].map((_, j) => (
                      <div key={j} className="flex items-center gap-2">
                        <Skeleton className="h-4 w-4 rounded-full flex-shrink-0" />
                        <Skeleton className="h-4 w-full" />
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Free Plan Card */}
      <div className="max-sm:px-4 max-tablet:px-6 w-full mb-16">
        <div className="relative w-full max-w-4xl mx-auto">
          <div className="bg-white border-2 border-primary/20 rounded-lg p-6 md:p-8">
            <div className="grid md:grid-cols-2 gap-6 md:gap-8 items-center">
              <div className="space-y-4">
                <Skeleton className="h-8 w-56" />
                <Skeleton className="h-6 w-32" />
                <div className="space-y-2">
                  {[...Array(5)].map((_, i) => (
                    <div key={i} className="flex items-center gap-2">
                      <Skeleton className="h-4 w-4 flex-shrink-0" />
                      <Skeleton className="h-4 w-40" />
                    </div>
                  ))}
                </div>
              </div>
              <div className="flex flex-col items-center justify-center bg-muted/30 rounded-lg p-6 space-y-4">
                <Skeleton className="h-4 w-64" />
                <Skeleton className="h-12 w-full max-w-xs" />
                <Skeleton className="h-3 w-32" />
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Assurance Section */}
      <div className="px-4 max-sm:px-4 max-tablet:px-6 w-full mb-16">
        <div className="relative w-full max-w-7xl mx-auto">
          <div className="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
            {[...Array(4)].map((_, i) => (
              <div key={i} className="text-center space-y-3">
                <Skeleton className="h-12 w-12 rounded-full mx-auto" />
                <Skeleton className="h-5 w-40 mx-auto" />
                <Skeleton className="h-4 w-full" />
                <Skeleton className="h-4 w-48 mx-auto" />
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* More Information Section */}
      <div className="max-sm:px-4 max-tablet:px-6 w-full mb-16">
        <div className="max-w-7xl mx-auto">
          <div className="text-center mb-12">
            <Skeleton className="h-10 w-96 mx-auto mb-4" />
            <Skeleton className="h-5 w-[600px] max-w-full mx-auto" />
          </div>
          <div className="grid md:grid-cols-3 gap-8">
            {[...Array(3)].map((_, i) => (
              <div key={i} className="text-center space-y-3">
                <Skeleton className="h-16 w-16 rounded-lg mx-auto" />
                <Skeleton className="h-6 w-32 mx-auto" />
                <Skeleton className="h-4 w-full" />
                <Skeleton className="h-4 w-full" />
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Comparison Section */}
      <div className="pt-20 max-sm:px-4 max-tablet:px-6 w-full mb-16">
        <div className="text-center mb-12">
          <Skeleton className={cn(
            "h-12 w-96 mx-auto",
            "max-sm:h-10 max-sm:w-64"
          )} />
        </div>
        <div className="max-w-7xl mx-auto">
          <Skeleton className="h-[600px] w-full rounded-lg" />
        </div>
      </div>

      {/* Feature Carousel */}
      <div className="max-sm:px-4 max-tablet:px-6 w-full mb-16">
        <div className="max-w-7xl mx-auto text-center">
          <Skeleton className="h-10 w-96 mx-auto mb-4" />
          <Skeleton className="h-5 w-[600px] max-w-full mx-auto mb-12" />
          <Skeleton className="h-96 w-full rounded-lg" />
        </div>
      </div>

      {/* Customer Testimonials */}
      <div className="max-sm:px-4 max-tablet:px-6 w-full mb-16">
        <div className="max-w-7xl mx-auto">
          <div className="text-center mb-12">
            <Skeleton className="h-10 w-96 mx-auto" />
          </div>
          <div className="grid md:grid-cols-2 gap-8">
            {[...Array(2)].map((_, i) => (
              <div key={i} className="border rounded-lg p-6 space-y-4">
                <div className="flex items-center gap-1">
                  {[...Array(5)].map((_, j) => (
                    <Skeleton key={j} className="h-5 w-5" />
                  ))}
                </div>
                <Skeleton className="h-4 w-full" />
                <Skeleton className="h-4 w-full" />
                <Skeleton className="h-4 w-3/4" />
                <div className="flex items-center gap-3 pt-4">
                  <Skeleton className="h-12 w-12 rounded-full" />
                  <Skeleton className="h-5 w-32" />
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* FAQ Section */}
      <div className="max-sm:px-4 max-tablet:px-6 w-full mb-16">
        <div className="max-w-3xl mx-auto">
          <div className="text-center mb-12">
            <Skeleton className="h-10 w-96 mx-auto" />
          </div>
          <div className="space-y-4">
            {[...Array(6)].map((_, i) => (
              <div key={i} className="border rounded-lg p-4">
                <Skeleton className="h-6 w-full" />
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Call to Action */}
      <div className="bg-primary/5 py-16 max-sm:px-4 max-tablet:px-6">
        <div className="max-w-4xl mx-auto text-center space-y-6">
          <Skeleton className="h-6 w-64 mx-auto" />
          <Skeleton className="h-12 w-96 mx-auto" />
          <Skeleton className="h-5 w-[600px] max-w-full mx-auto" />
          <Skeleton className="h-12 w-48 mx-auto" />
        </div>
      </div>
    </div>
  );
}
import { cn } from "@/lib/utils";
import { useRouter } from "next/navigation";
import { RetroGrid } from "@/components/ui/retro-grid";
import { ShimmerButton } from "@/components/ui/shimmer-button";

interface CallToActionSectionProps {
    preHeading?: string;
    heading?: string;
    postHeading?: string;
    className?: string;
}

export function CallToActionSection({
    preHeading = "Ready to get started?",
    heading = `Get Started with ${process.env.NEXT_PUBLIC_APP_NAME} Today!`,
    postHeading = "Join thousands of teams using powerful collaboration tools to improve their workflow.",
    className,
}: CallToActionSectionProps) {
    const router = useRouter();

    return (
        <div className={cn(
            "relative py-8 md:py-16 px-4 md:px-6 lg:px-8 text-center overflow-hidden bg-gradient-to-br from-teal-50 via-orange-50/30 to-teal-50",
            className
        )}>
            {/* Animated Background */}
            <RetroGrid
                className="opacity-40"
                angle={65}
                cellSize={80}
                lightLineColor="rgba(0, 95, 90, 0.4)"
                darkLineColor="rgba(0, 95, 90, 0.4)"
            />

            {/* Decorative Elements */}
            <div className="absolute inset-0 opacity-20 pointer-events-none">
                <div className="absolute top-0 left-1/4 w-64 md:w-96 h-64 md:h-96 bg-primary rounded-full mix-blend-multiply filter blur-3xl animate-pulse"></div>
                <div className="absolute bottom-0 right-1/4 w-64 md:w-96 h-64 md:h-96 bg-secondary rounded-full mix-blend-multiply filter blur-3xl animate-pulse animation-delay-2000"></div>
            </div>

            <div className="relative z-10 max-w-4xl mx-auto">
                {preHeading && (
                    <p className="text-base md:text-lg text-primary mb-3 md:mb-4 font-medium">
                        {preHeading}
                    </p>
                )}

                <h2 className="text-3xl md:text-4xl lg:text-5xl font-bold mb-4 md:mb-6 text-foreground">
                    {heading}
                </h2>

                {postHeading && (
                    <p className="text-base md:text-lg lg:text-xl text-muted-foreground mb-6 md:mb-8 max-w-2xl mx-auto">
                        {postHeading}
                    </p>
                )}

                <div className="flex flex-col sm:flex-row gap-3 md:gap-4 justify-center">
                    <ShimmerButton
                        className="px-6 md:px-8 py-3 md:py-4 text-base md:text-lg font-semibold"
                        shimmerColor="#ffffff"
                        shimmerSize="0.1em"
                        shimmerDuration="2.5s"
                        background="rgba(0, 95, 90, 1)"
                        onClick={() => router.push('/login')}
                    >
                        Start Free Trial
                    </ShimmerButton>
                </div>

                <p className="text-xs md:text-sm text-muted-foreground mt-4 md:mt-6">
                    No credit card required • Cancel anytime • 7-day free trial
                </p>
            </div>
        </div>
    );
}
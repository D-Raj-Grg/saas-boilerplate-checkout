import { Loader2 } from "lucide-react";
import { Logo } from "@/components/ui/logo";

export default function RootLoading() {
  return (
    <div className="min-h-screen bg-background flex items-center justify-center">
      <div className="flex flex-col items-center gap-6">
        <div className="h-8 w-auto opacity-20">
          <Logo />
        </div>
        <Loader2 className="h-8 w-8 animate-spin text-primary opacity-40" />
      </div>
    </div>
  );
}
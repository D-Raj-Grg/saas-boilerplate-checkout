"use client";

import Image from "next/image";
import { cn } from "@/lib/utils";

interface LogoProps {
  collapsed?: boolean;
  className?: string;
}

export function Logo({ collapsed = false, className }: LogoProps) {
  return (
    <div className={cn("flex items-center gap-2 w-full h-full", className)}>
      {collapsed ? (
        <Image
          src="/logo.svg"
          alt={`${process.env.NEXT_PUBLIC_APP_NAME} Logo` || "Logo"}
          width={32}
          height={32}
          className="w-full h-full object-contain"
        />
      ) : (
        <>
          <Image
            src="/logo.svg"
            alt={`${process.env.NEXT_PUBLIC_APP_NAME} Logo` || "Logo"}
            width={32}
            height={32}
            className="h-full w-auto object-contain"
          />
          <Image
            src="/logo-text.svg"
            alt={process.env.NEXT_PUBLIC_APP_NAME || "Logo"}
            width={70}
            height={24}
            className="h-auto w-auto object-contain"
          />
        </>
      )}
    </div>
  );
}
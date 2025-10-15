"use client";

import * as React from "react";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger
} from "@/components/ui/tooltip";

interface TooltipChildProps {
  children: React.ReactNode;
  content: string;
  delayDuration?: number;
  side?: 'top' | 'right' | 'bottom' | 'left';
  contentClassName?: string;
}

export function TooltipChild({
  children,
  content,
  delayDuration = 200,
  side = 'top',
  contentClassName = ''
}: TooltipChildProps) {
  return (
    <TooltipProvider delayDuration={delayDuration}>
      <Tooltip>
        <TooltipTrigger asChild>
          {children}
        </TooltipTrigger>
        {content && (
          <TooltipContent
            side={side}
            className={contentClassName}
          >
            {content}
          </TooltipContent>
        )}
      </Tooltip>
    </TooltipProvider>
  );
}
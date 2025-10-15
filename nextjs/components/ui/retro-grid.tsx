"use client";

import { cn } from "@/lib/utils";

interface RetroGridProps {
  className?: string;
  angle?: number;
  cellSize?: number;
  opacity?: number;
  lightLineColor?: string;
  darkLineColor?: string;
}

export function RetroGrid({
  className,
  angle = 65,
  cellSize = 60,
  opacity = 0.5,
  lightLineColor = "rgba(0, 95, 90, 0.3)",
  darkLineColor = "rgba(0, 95, 90, 0.3)",
}: RetroGridProps) {
  return (
    <div
      className={cn(
        "pointer-events-none absolute inset-0 overflow-hidden opacity-50 [perspective:200px]",
        className
      )}
      style={{
        "--grid-angle": `${angle}deg`,
        "--cell-size": `${cellSize}px`,
        "--grid-opacity": opacity,
        "--light-line-color": lightLineColor,
        "--dark-line-color": darkLineColor,
      } as React.CSSProperties}
    >
      <div className="absolute inset-0 [transform:rotateX(var(--grid-angle))]">
        <div
          className={cn(
            "animate-grid",
            "[background-repeat:repeat] [background-size:var(--cell-size)_var(--cell-size)] [height:300vh] [inset:0%_0px] [margin-left:-50%] [transform-origin:100%_0_0] [width:600vw]",
            "[background-image:linear-gradient(to_right,var(--light-line-color)_1px,transparent_0),linear-gradient(to_bottom,var(--light-line-color)_1px,transparent_0)]",
            "dark:[background-image:linear-gradient(to_right,var(--dark-line-color)_1px,transparent_0),linear-gradient(to_bottom,var(--dark-line-color)_1px,transparent_0)]"
          )}
        />
      </div>
      <div className="absolute inset-0 bg-gradient-to-t from-white to-transparent dark:from-black" />
    </div>
  );
}
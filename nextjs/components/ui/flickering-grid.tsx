"use client";

import { useEffect, useRef } from "react";
import { cn } from "@/lib/utils";

interface FlickeringGridProps {
  className?: string;
  squareSize?: number;
  gridGap?: number;
  flickerChance?: number;
  color?: string;
  maxOpacity?: number;
  width?: number;
  height?: number;
}

export function FlickeringGrid({
  className,
  squareSize = 4,
  gridGap = 6,
  flickerChance = 0.3,
  color = "rgb(0, 95, 90)",
  maxOpacity = 0.3,
  width,
  height,
}: FlickeringGridProps) {
  const canvasRef = useRef<HTMLCanvasElement>(null);

  useEffect(() => {
    const canvas = canvasRef.current;
    if (!canvas) return;

    const ctx = canvas.getContext("2d");
    if (!ctx) return;

    let animationFrameId: number;

    const resizeCanvas = () => {
      const rect = canvas.getBoundingClientRect();
      canvas.width = width || rect.width;
      canvas.height = height || rect.height;
    };

    resizeCanvas();
    window.addEventListener("resize", resizeCanvas);

    const cols = Math.ceil(canvas.width / (squareSize + gridGap));
    const rows = Math.ceil(canvas.height / (squareSize + gridGap));

    const squares: { opacity: number; targetOpacity: number }[][] = Array(rows)
      .fill(null)
      .map(() =>
        Array(cols)
          .fill(null)
          .map(() => ({
            opacity: 0,
            targetOpacity: 0,
          }))
      );

    const drawSquare = (x: number, y: number, opacity: number) => {
      ctx.fillStyle = color.replace(")", `, ${opacity})`).replace("rgb", "rgba");
      ctx.fillRect(
        x * (squareSize + gridGap),
        y * (squareSize + gridGap),
        squareSize,
        squareSize
      );
    };

    const updateSquares = () => {
      for (let y = 0; y < rows; y++) {
        for (let x = 0; x < cols; x++) {
          const square = squares[y][x];

          if (Math.random() < flickerChance) {
            square.targetOpacity = Math.random() * maxOpacity;
          }

          square.opacity += (square.targetOpacity - square.opacity) * 0.1;
        }
      }
    };

    const render = () => {
      ctx.clearRect(0, 0, canvas.width, canvas.height);

      updateSquares();

      for (let y = 0; y < rows; y++) {
        for (let x = 0; x < cols; x++) {
          drawSquare(x, y, squares[y][x].opacity);
        }
      }

      animationFrameId = requestAnimationFrame(render);
    };

    render();

    return () => {
      window.removeEventListener("resize", resizeCanvas);
      cancelAnimationFrame(animationFrameId);
    };
  }, [squareSize, gridGap, flickerChance, color, maxOpacity, width, height]);

  return (
    <canvas
      ref={canvasRef}
      className={cn(
        "absolute inset-0 h-full w-full",
        className
      )}
    />
  );
}
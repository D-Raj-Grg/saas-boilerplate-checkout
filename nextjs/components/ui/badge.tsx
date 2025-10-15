import * as React from "react"
import { Slot } from "@radix-ui/react-slot"
import { cva, type VariantProps } from "class-variance-authority"

import { cn } from "@/lib/utils"

const badgeVariants = cva(
  "inline-flex items-center justify-center rounded-full border px-2.5 py-0.5 text-xs font-medium w-fit whitespace-nowrap shrink-0 [&>svg]:size-3 gap-1 [&>svg]:pointer-events-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive transition-all duration-200 overflow-hidden",
  {
    variants: {
      variant: {
        default:
          "border-transparent bg-primary text-primary-foreground [a&]:hover:bg-primary/90 shadow-sm",
        secondary:
          "border-transparent bg-secondary text-secondary-foreground [a&]:hover:bg-secondary/90 shadow-sm",
        destructive:
          "border-transparent bg-destructive text-white [a&]:hover:bg-destructive/90 focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40 dark:bg-destructive/60",
        outline:
          "border-primary/30 text-primary bg-primary/10 [a&]:hover:bg-primary/20 [a&]:hover:border-primary/50",
        teal:
          "border-teal-200 text-teal-700 bg-teal-50 [a&]:hover:bg-teal-200 [a&]:hover:border-teal-500",
        draft:
          "border-gray-200 text-gray-600 bg-gray-50 [a&]:hover:bg-gray-200 [a&]:hover:border-gray-500",
        running:
          "border-green-200 text-green-600 bg-green-50 [a&]:hover:bg-green-200 [a&]:hover:border-green-500",
        paused:
          "border-amber-200 text-amber-600 bg-amber-50 [a&]:hover:bg-amber-200 [a&]:hover:border-amber-500",
        completed:
          "border-sky-200 text-sky-600 bg-sky-50 [a&]:hover:bg-sky-200 [a&]:hover:border-sky-500",
        scheduled:
          "border-purple-200 text-purple-600 bg-purple-50 [a&]:hover:bg-purple-200 [a&]:hover:border-purple-500",
        sky:
          "border-sky-200 text-sky-600 bg-sky-50 [a&]:hover:bg-sky-200 [a&]:hover:border-sky-500",
        archived:
          "border-gray-200 text-gray-600 bg-gray-50 [a&]:hover:bg-gray-200 [a&]:hover:border-gray-500"
      },
    },
    defaultVariants: {
      variant: "default",
    },
  }
)

function Badge({
  className,
  variant,
  asChild = false,
  ...props
}: React.ComponentProps<"span"> &
  VariantProps<typeof badgeVariants> & { asChild?: boolean }) {
  const Comp = asChild ? Slot : "span"

  return (
    <Comp
      data-slot="badge"
      className={cn(badgeVariants({ variant }), className)}
      {...props}
    />
  )
}

export { Badge, badgeVariants }

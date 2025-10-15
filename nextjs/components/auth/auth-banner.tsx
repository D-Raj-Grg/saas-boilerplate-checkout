"use client";

import { motion } from "framer-motion";
import Image from "next/image";
import { DotPattern } from "@/components/ui/dot-pattern";
import { cn } from "@/lib/utils";

interface StatBadge {
  value: string;
  label: string;
}

interface AuthBannerProps {
  heading: string;
  description: string;
  badges?: StatBadge[];
  className?: string;
}

export function AuthBanner({
  heading,
  description,
  badges = [],
  className,
}: AuthBannerProps) {
  return (
    <div className={cn("hidden lg:block lg:w-1/2 relative overflow-hidden", className)}>
      {/* Background layer with mask */}
      <div className="absolute inset-0 bg-gradient-to-br from-teal-50 to-cyan-50 [mask-image:linear-gradient(to_right,transparent,white)]" />

      {/* Dot Pattern Background */}
      <DotPattern
        width={20}
        height={20}
        cx={1}
        cy={1}
        cr={1}
        className="fill-primary/40 [mask-image:linear-gradient(to_right,transparent,white)]"
      />

      {/* Content Container - NO MASK, stays fully visible */}
      <div className="relative z-10 flex h-full flex-col items-center justify-center p-12 text-primary">
        {/* Logo */}
        <motion.div
          initial={{ opacity: 0, y: -20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6, ease: "easeOut" }}
          className="mb-12"
        >
          <div className="relative">
            <div className="absolute inset-0 bg-primary/10 blur-xl rounded-full" />
            <Image
              src="/logo-full.svg"
              alt={process.env.NEXT_PUBLIC_APP_NAME || "Logo"}
              width={180}
              height={50}
              className="relative"
              priority
            />
          </div>
        </motion.div>

        {/* Heading */}
        <motion.h2
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6, delay: 0.2, ease: "easeOut" }}
          className="mb-6 text-5xl font-bold leading-tight text-center"
        >
          {heading.split('\n').map((line, i) => (
            <span key={i}>
              {line}
              {i < heading.split('\n').length - 1 && <br />}
            </span>
          ))}
        </motion.h2>

        {/* Description */}
        <motion.p
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6, delay: 0.3, ease: "easeOut" }}
          className="mb-8 max-w-md text-lg text-gray-700 text-center"
        >
          {description}
        </motion.p>

        {/* Badges/Stats */}
        {badges.length > 0 && (
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6, delay: 0.4, ease: "easeOut" }}
            className="flex gap-8 flex-wrap justify-center"
          >
            {badges.map((badge, index) => (
              <motion.div
                key={index}
                initial={{ opacity: 0, scale: 0.8 }}
                animate={{ opacity: 1, scale: 1 }}
                transition={{
                  duration: 0.5,
                  delay: 0.5 + index * 0.1,
                  ease: "easeOut",
                }}
                whileHover={{ scale: 1.05 }}
                className="text-center group cursor-default"
              >
                <div className="relative">
                  {/* Glow effect on hover */}
                  <div className="absolute inset-0 bg-primary/0 group-hover:bg-primary/20 blur-xl rounded-full transition-all duration-300" />
                  <div className="relative text-4xl font-bold group-hover:scale-110 transition-transform duration-300 text-primary">
                    {badge.value}
                  </div>
                </div>
                <div className="text-sm text-gray-600 mt-1">{badge.label}</div>
              </motion.div>
            ))}
          </motion.div>
        )}

        {/* Floating decorative elements */}
        <div className="absolute inset-0 overflow-hidden pointer-events-none">
          {[...Array(3)].map((_, i) => (
            <motion.div
              key={i}
              className="absolute w-64 h-64 bg-primary/5 rounded-full blur-3xl"
              initial={{ opacity: 0 }}
              animate={{
                opacity: [0.2, 0.4, 0.2],
                x: [0, 30, 0],
                y: [0, -30, 0],
              }}
              transition={{
                duration: 8 + i * 2,
                repeat: Infinity,
                delay: i * 2,
                ease: "easeInOut",
              }}
              style={{
                left: `${20 + i * 30}%`,
                top: `${30 + i * 20}%`,
              }}
            />
          ))}
        </div>
      </div>
    </div>
  );
}

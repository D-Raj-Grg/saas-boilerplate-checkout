"use client";

import { motion } from "framer-motion";
import { cn } from "@/lib/utils";
import { Shield, CheckCircle2, Clock, Sparkles } from "lucide-react";

interface MoneyBackGuaranteeProps {
    className?: string;
}

const features = [
    {
        icon: Shield,
        text: "100% Secure",
    },
    {
        icon: CheckCircle2,
        text: "No Questions Asked",
    },
    {
        icon: Clock,
        text: "Instant Refund",
    },
];

export function MoneyBackGuarantee({ className }: MoneyBackGuaranteeProps) {
    return (
        <div className={cn(
            "w-full py-8 md:py-16 flex justify-center relative px-4 md:px-6 lg:px-8",
            className
        )}>
            <div className="w-full max-w-6xl xl:max-w-7xl">
                <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    whileInView={{ opacity: 1, y: 0 }}
                    viewport={{ once: true }}
                    transition={{ duration: 0.5 }}
                    className="relative overflow-hidden bg-gradient-to-br from-teal-600 via-teal-700 to-teal-800 rounded-2xl md:rounded-3xl p-8 md:p-12 lg:p-16 xl:p-20 shadow-2xl"
                >
                    {/* Animated background pattern */}
                    <div className="absolute inset-0 opacity-10">
                        <div className="absolute top-0 -left-4 w-72 h-72 bg-white rounded-full mix-blend-multiply filter blur-xl animate-blob" />
                        <div className="absolute top-0 -right-4 w-72 h-72 bg-orange-300 rounded-full mix-blend-multiply filter blur-xl animate-blob animation-delay-2000" />
                        <div className="absolute -bottom-8 left-20 w-72 h-72 bg-teal-300 rounded-full mix-blend-multiply filter blur-xl animate-blob animation-delay-4000" />
                    </div>

                    <div className="relative flex flex-col lg:flex-row items-center gap-6 md:gap-8 lg:gap-12 xl:gap-16">
                        {/* Left Side - Days Badge */}
                        <motion.div
                            initial={{ scale: 0.8, opacity: 0 }}
                            whileInView={{ scale: 1, opacity: 1 }}
                            viewport={{ once: true }}
                            transition={{ delay: 0.2, type: "spring", stiffness: 200 }}
                            className="flex-shrink-0"
                        >
                            <div className="relative">
                                {/* Glow effect */}
                                <div className="absolute inset-0 bg-white rounded-2xl md:rounded-3xl blur-xl opacity-20 animate-pulse" />

                                <div className="relative bg-white rounded-2xl md:rounded-3xl p-8 md:p-10 lg:p-12 shadow-2xl border-4 md:border-[5px] border-orange-500 min-w-[140px] md:min-w-[180px] lg:min-w-[220px]">
                                    <div className="text-center">
                                        <motion.div
                                            initial={{ scale: 1 }}
                                            animate={{ scale: [1, 1.05, 1] }}
                                            transition={{ duration: 2, repeat: Infinity, repeatDelay: 1 }}
                                            className="text-6xl md:text-8xl lg:text-9xl font-black bg-gradient-to-br from-teal-600 to-teal-800 bg-clip-text text-transparent leading-none"
                                        >
                                            7
                                        </motion.div>
                                        <div className="text-sm md:text-base lg:text-lg font-bold text-gray-600 uppercase tracking-widest mt-2 md:mt-3">
                                            DAYS
                                        </div>
                                    </div>

                                    {/* Decorative sparkle */}
                                    <motion.div
                                        animate={{ rotate: 360 }}
                                        transition={{ duration: 3, repeat: Infinity, ease: "linear" }}
                                        className="absolute -top-2 -right-2 md:-top-3 md:-right-3"
                                    >
                                        <Sparkles className="w-6 h-6 md:w-7 md:h-7 lg:w-8 lg:h-8 text-orange-500" fill="currentColor" />
                                    </motion.div>
                                </div>
                            </div>
                        </motion.div>

                        {/* Right Side - Content */}
                        <div className="flex-1 text-center lg:text-left">
                            <motion.h3
                                initial={{ opacity: 0, x: -20 }}
                                whileInView={{ opacity: 1, x: 0 }}
                                viewport={{ once: true }}
                                transition={{ delay: 0.3 }}
                                className="text-3xl md:text-4xl lg:text-5xl xl:text-6xl font-bold text-white mb-4 md:mb-5 lg:mb-6 tracking-tight"
                            >
                                100% Money-Back Guarantee
                            </motion.h3>

                            <motion.p
                                initial={{ opacity: 0, x: -20 }}
                                whileInView={{ opacity: 1, x: 0 }}
                                viewport={{ once: true }}
                                transition={{ delay: 0.4 }}
                                className="text-teal-50 text-base md:text-lg lg:text-xl leading-relaxed mb-6 md:mb-8 max-w-3xl mx-auto lg:mx-0"
                            >
                                Try {process.env.NEXT_PUBLIC_APP_NAME} risk-free for 7 days. If you&apos;re not completely satisfied,
                                we&apos;ll refund your purchase - no questions asked. We&apos;re confident you&apos;ll
                                love how easy it is to optimize your conversion rates.
                            </motion.p>

                            {/* Feature badges */}
                            <motion.div
                                initial={{ opacity: 0, y: 10 }}
                                whileInView={{ opacity: 1, y: 0 }}
                                viewport={{ once: true }}
                                transition={{ delay: 0.5 }}
                                className="flex flex-wrap items-center justify-center lg:justify-start gap-3 md:gap-4 lg:gap-5"
                            >
                                {features.map((feature, index) => (
                                    <motion.div
                                        key={index}
                                        initial={{ opacity: 0, scale: 0.8 }}
                                        whileInView={{ opacity: 1, scale: 1 }}
                                        viewport={{ once: true }}
                                        transition={{ delay: 0.6 + index * 0.1 }}
                                        whileHover={{ scale: 1.05, y: -2 }}
                                        className="flex items-center gap-2 md:gap-2.5 bg-white/10 backdrop-blur-sm border border-white/30 rounded-full px-4 md:px-5 lg:px-6 py-2 md:py-2.5 lg:py-3"
                                    >
                                        <feature.icon className="w-4 h-4 md:w-5 md:h-5 lg:w-5 lg:h-5 text-orange-400" />
                                        <span className="text-sm md:text-base lg:text-base font-medium text-white">
                                            {feature.text}
                                        </span>
                                    </motion.div>
                                ))}
                            </motion.div>
                        </div>
                    </div>
                </motion.div>
            </div>
        </div>
    );
}

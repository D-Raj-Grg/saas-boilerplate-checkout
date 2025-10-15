"use client";

import { Marquee } from "@/components/ui/marquee";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { cn } from "@/lib/utils";
import { Star } from "lucide-react";
import { motion } from "framer-motion";

interface Testimonial {
  rating: number;
  review: string;
  highlight?: string;
  author: string;
  authorImage?: string;
}

const testimonials: Testimonial[] = [
  {
    rating: 5,
    review:
      `Had some initial setup challenges getting started. The ${process.env.NEXT_PUBLIC_APP_NAME} team was quick to respond and helped us get everything working perfectly.`,
    highlight: "Excellent support, very happy with the results!",
    author: "Sarah Chen",
    authorImage: "slim.png",
  },
  {
    rating: 4.5,
    review:
      "I have tried many collaboration platforms for our e-commerce business. Set up our team workspace in minutes, and the intuitive interface made onboarding seamless. Our team productivity increased by 23%.",
    highlight: `${process.env.NEXT_PUBLIC_APP_NAME} is BY FAR the best tool`,
    author: "Marcus Rodriguez",
    authorImage: "maffar.png",
  },
  {
    rating: 5,
    review:
      "The platform helped our startup streamline team collaboration and increase productivity by 40%. The analytics are comprehensive and the reporting is crystal clear. Highly recommend!",
    highlight: "Amazing experience!",
    author: "Emily Watson",
    authorImage: "miriam.png",
  },
  {
    rating: 5,
    review:
      "Best one I have used. Provides reliable analytics and meaningful insights. Minimal learning curve required. I'm a professional project manager and this is my go-to tool.",
    highlight: "Outstanding collaboration platform.",
    author: "David Kim",
    authorImage: "timothy.png",
  },
  {
    rating: 5,
    review:
      `${process.env.NEXT_PUBLIC_APP_NAME} has transformed how we manage our AI website builder team. The insights we've gained have directly improved our workflow and helped us make data-driven decisions that matter.`,
    highlight: "Game-changing platform for product teams",
    author: "ZipWP Team",
    authorImage: "zipwp-logo.png",
  },
  {
    rating: 5,
    review:
      `As a theme trusted by millions, we needed a robust collaboration solution for our distributed team. ${process.env.NEXT_PUBLIC_APP_NAME} delivers exactly that with clean analytics and reliable performance that guide our product roadmap.`,
    highlight: "Essential tool for serious teams",
    author: "Astra Theme Team",
    authorImage: "astra-logo.png",
  },
  {
    rating: 5,
    review:
      `Managing our AI automation platform requires precision and speed. ${process.env.NEXT_PUBLIC_APP_NAME} provides both, allowing us to iterate quickly and confidently on features that drive real user engagement.`,
    highlight: "Perfect for rapid iteration",
    author: "OttoKit Team",
    authorImage: "ottokit-logo.png",
  },
  {
    rating: 5,
    review:
      `For our SEO platform, understanding team performance is critical. ${process.env.NEXT_PUBLIC_APP_NAME} gives us the insights we need to optimize our workflows and improve team collaboration with data-backed decisions.`,
    highlight: "Invaluable for team optimization",
    author: "SureRank Team",
    authorImage: "surerank-logo.png",
  },
];

const firstRow = testimonials.slice(0, Math.ceil(testimonials.length / 2));
const secondRow = testimonials.slice(Math.ceil(testimonials.length / 2));

function TestimonialCard({ testimonial }: { testimonial: Testimonial }) {
  return (
    <motion.div
      whileHover={{ scale: 1.02, y: -4 }}
      transition={{ type: "spring", stiffness: 300, damping: 20 }}
      className="relative w-[350px] md:w-[400px] cursor-pointer"
    >
      <div className="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300 h-full">
        {/* Review Text */}
        <div className="text-gray-700 text-sm leading-relaxed mb-4">
          {testimonial.review}
          {testimonial.highlight && (
            <>
              {" "}
              <span className="bg-secondary/10 text-secondary font-semibold px-1.5 py-0.5 rounded">
                {testimonial.highlight}
              </span>
            </>
          )}
        </div>

        {/* Author */}
        <div className="flex items-center gap-3 pt-4 border-t border-gray-100">
          <Avatar className="h-10 w-10 ring-2 ring-primary/10">
            {testimonial.authorImage && (
              <AvatarImage
                src={
                  testimonial.authorImage.startsWith("http")
                    ? testimonial.authorImage
                    : testimonial.authorImage.includes("logo")
                    ? `/images/testimonials/${testimonial.authorImage}`
                    : `/images/pricing/st-pricing/${testimonial.authorImage}`
                }
                alt={testimonial.author}
              />
            )}
            <AvatarFallback className="bg-gradient-to-br from-primary to-primary/80 text-white text-sm font-semibold">
              {testimonial.author[0]}
            </AvatarFallback>
          </Avatar>
          <div className="flex flex-col">
            <div className="text-sm font-semibold text-gray-900">
              {testimonial.author}
            </div>
            {/* Star Rating */}
            <div className="flex gap-1">
              {Array.from({ length: 5 }).map((_, index) => (
                <Star
                  key={index}
                  className={cn(
                    "w-4 h-4",
                    testimonial.rating > index
                      ? Math.ceil(testimonial.rating) === index + 1 &&
                        testimonial.rating % 1 === 0.5
                        ? "text-primary fill-primary/50"
                        : "text-primary fill-primary"
                      : "text-gray-300"
                  )}
                />
              ))}
            </div>
          </div>
        </div>
      </div>
    </motion.div>
  );
}

export function TestimonialsMarquee() {
  return (
    <div className="w-full py-8 md:py-16 bg-gradient-to-b from-white via-teal-50/30 to-white">
      <div className="w-full max-w-6xl xl:max-w-7xl mx-auto px-4 md:px-6 lg:px-8 mb-8 md:mb-12">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.5 }}
          className="text-center"
        >
          <h2 className="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
            Loved by Teams Worldwide
          </h2>
          <p className="text-gray-600 text-lg max-w-2xl mx-auto">
            Join thousands of teams using {process.env.NEXT_PUBLIC_APP_NAME} to streamline their workflows
          </p>
        </motion.div>
      </div>

      <div className="relative flex flex-col items-center justify-center overflow-hidden gap-4 md:gap-6">
        {/* First Row - Left to Right */}
        <Marquee pauseOnHover className="[--duration:60s] [--gap:1.5rem]">
          {firstRow.map((testimonial, index) => (
            <TestimonialCard key={`first-${index}`} testimonial={testimonial} />
          ))}
        </Marquee>

        {/* Second Row - Right to Left */}
        <Marquee
          reverse={true}
          pauseOnHover
          className="[--duration:60s] [--gap:1.5rem]"
        >
          {secondRow.map((testimonial, index) => (
            <TestimonialCard
              key={`second-${index}`}
              testimonial={testimonial}
            />
          ))}
        </Marquee>

        {/* Gradient Overlays */}
        <div className="pointer-events-none absolute inset-y-0 left-0 w-32 bg-gradient-to-r from-white via-white/80 to-transparent"></div>
        <div className="pointer-events-none absolute inset-y-0 right-0 w-32 bg-gradient-to-l from-white via-white/80 to-transparent"></div>
      </div>
    </div>
  );
}

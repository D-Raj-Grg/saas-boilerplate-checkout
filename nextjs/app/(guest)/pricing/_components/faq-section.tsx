"use client";

import { motion } from "framer-motion";
import { cn } from "@/lib/utils";
import Link from "next/link";
import {
  Sparkles,
  Zap,
  Plug,
  Shield,
  Clock,
  Code,
  HeadphonesIcon,
  CreditCard,
  MessageCircle,
  Users,
} from "lucide-react";
import FAQComponent from "@/components/smoothui/ui/FaqListingComponent";

const saasBoilerplateFaqs = [
  {
    question: "What's included in the pricing plans?",
    answer:
      "All pricing plans include core features like organization management, team collaboration, workspace creation, and advanced analytics. Higher-tier plans unlock additional features like custom domains, SSO integration, priority support, and increased usage limits for teams and workspaces.",
    icon: <Users className="w-5 h-5" />,
  },
  {
    question: `How is ${process.env.NEXT_PUBLIC_APP_NAME} different from other platforms?`,
    answer:
      `${process.env.NEXT_PUBLIC_APP_NAME} is built with modern technology and best practices, offering a seamless user experience with powerful features. Our platform is designed for scalability, security, and ease of use, making it perfect for teams of all sizes.`,
    icon: <Zap className="w-5 h-5" />,
  },
  {
    question: `Can I integrate ${process.env.NEXT_PUBLIC_APP_NAME} with my existing tools?`,
    answer:
      `Yes, ${process.env.NEXT_PUBLIC_APP_NAME} offers comprehensive API access and webhooks that work seamlessly with popular tools and services. We provide detailed documentation and integration guides to make the setup process straightforward.`,
    icon: <Plug className="w-5 h-5" />,
  },
  {
    question: "What payment methods do you accept?",
    answer:
      "We accept all major credit cards (Visa, MasterCard, American Express, Discover) and support various payment processors. All payments are processed securely through industry-standard encryption.",
    icon: <CreditCard className="w-5 h-5" />,
  },
  {
    question: "Can I change my plan later?",
    answer:
      "Yes, you can upgrade or downgrade your plan at any time. When you upgrade, you'll be charged a prorated amount for the remainder of your billing cycle. When you downgrade, the change will take effect at the start of your next billing cycle.",
    icon: <Clock className="w-5 h-5" />,
  },
  {
    question: `Do I need technical knowledge to use ${process.env.NEXT_PUBLIC_APP_NAME}?`,
    answer:
      `No, ${process.env.NEXT_PUBLIC_APP_NAME} is designed to be user-friendly for both technical and non-technical users. Our intuitive interface makes it easy to get started, while developers can leverage our powerful APIs for advanced customization.`,
    icon: <Code className="w-5 h-5" />,
  },
  {
    question: "What kind of support do you provide?",
    answer:
      "We provide comprehensive support including email support for all plans, priority support for Pro and Business plans, detailed documentation, video tutorials, and a community forum. Our team is always ready to help you succeed.",
    icon: <HeadphonesIcon className="w-5 h-5" />,
  },
  {
    question: "Is there a free trial available?",
    answer: (
      <>
        <p className="mb-3">
          Yes, we offer a 7-day free trial that includes access to core features. Here&apos;s what you get:
        </p>
        <div className="space-y-3">
          <div>
            <p className="font-semibold text-foreground mb-1">Usage Limits</p>
            <ul className="space-y-1 text-sm">
              <li>• 1 organization</li>
              <li>• 50 team members</li>
              <li>• 20 workspaces</li>
            </ul>
          </div>
          <div>
            <p className="font-semibold text-foreground mb-1">Core Features</p>
            <ul className="space-y-1 text-sm">
              <li>• Full organization management</li>
              <li>• Team collaboration tools</li>
              <li>• Email notifications</li>
              <li>• Basic analytics</li>
            </ul>
          </div>
          <div>
            <p className="font-semibold text-foreground mb-1">Data & Security</p>
            <ul className="space-y-1 text-sm">
              <li>• Secure data storage</li>
              <li>• Role-based permissions</li>
              <li>• Activity audit logs</li>
            </ul>
          </div>
        </div>
        <p className="mt-3 text-sm">
          You can upgrade at any time to unlock advanced features.
        </p>
      </>
    ),
    icon: <Sparkles className="w-5 h-5" />,
  },
  {
    question: "How do you ensure data security?",
    answer:
      `${process.env.NEXT_PUBLIC_APP_NAME} takes security seriously. We use industry-standard encryption, secure data centers, regular security audits, and compliance with major data protection regulations. Your data is backed up regularly and stored securely.`,
    icon: <Shield className="w-5 h-5" />,
  },
  {
    question:
      "I have questions about my specific use case. Can I speak to someone?",
    answer: (
      <>
        If you have specific questions about your use case, we&apos;re here to
        help. Our team is available to discuss how {process.env.NEXT_PUBLIC_APP_NAME} can best serve your needs. Feel free to reach
        out through our{" "}
        <Link
          href={`mailto:support@${typeof window !== 'undefined' ? window.location.hostname : 'example.com'}`}
          className="text-teal-600 hover:text-teal-700 underline font-medium transition-colors"
        >
          support team
        </Link>{" "}
        or schedule a demo call with us.
      </>
    ),
    icon: <MessageCircle className="w-5 h-5" />,
  },
];

interface FAQSectionProps {
  className?: string;
}

export function FAQSection({ className }: FAQSectionProps) {
  return (
    <div
      className={cn(
        "w-full py-8 md:py-16 flex justify-center relative px-4 md:px-6 lg:px-8",
        className
      )}
    >
      <div className="w-full max-w-6xl xl:max-w-7xl">
        {/* Section Header */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.2 }}
          className="text-center mb-8 md:mb-12 lg:mb-16"
        >
          <h2 className="text-5xl font-bold text-gray-900 mb-4 max-sm:text-3xl">
            Frequently Asked Questions
          </h2>
          <p className="text-gray-600 text-lg max-w-2xl mx-auto">
            Everything you need to know about {process.env.NEXT_PUBLIC_APP_NAME}
          </p>
        </motion.div>

        {/* FAQ Grid */}
        <div className="grid lg:grid-cols-[300px_1fr] gap-12 items-start max-lg:gap-8">
          {/* Left Sidebar - Order 2 on mobile, 1 on desktop */}
          <motion.div
            initial={{ opacity: 0, x: -10 }}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.2, delay: 0.1 }}
            className="lg:sticky lg:top-24 max-lg:text-center order-2 lg:order-1"
          >
            <div className="relative">
              {/* Decorative element */}
              <div className="absolute -top-4 -left-4 w-20 h-20 bg-orange-100 rounded-full blur-2xl opacity-50" />

              <div className="relative bg-gradient-to-br from-teal-50 to-orange-50 rounded-2xl p-6 border border-teal-100">
                <Sparkles className="w-8 h-8 text-teal-600 mb-3 max-lg:mx-auto" />
                <h3 className="text-2xl font-bold text-gray-900 mb-2">
                  Still have questions?
                </h3>
                <p className="text-gray-600 text-sm mb-4">
                  Can&apos;t find the answer you&apos;re looking for? Our team
                  is here to help.
                </p>
                <Link
                  href={`mailto:support@${typeof window !== 'undefined' ? window.location.hostname : 'example.com'}`}
                  className="inline-flex items-center justify-center w-full bg-teal-600 hover:bg-teal-700 text-white font-semibold px-4 py-2.5 rounded-lg transition-colors"
                >
                  Contact Support
                </Link>
              </div>
            </div>
          </motion.div>

          {/* FAQ Listing - Order 1 on mobile, 2 on desktop */}
          <div className="order-1 lg:order-2">
            <FAQComponent faqs={saasBoilerplateFaqs} />
          </div>
        </div>
      </div>
    </div>
  );
}

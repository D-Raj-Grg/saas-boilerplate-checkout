"use client"

import type React from "react"

import { useEffect, useRef, useState } from "react"
import { AnimatePresence, motion } from "motion/react"
import { useOnClickOutside } from "usehooks-ts"

export interface FAQ {
  question: string
  answer: string | React.ReactNode
  icon: React.ReactNode
}

export interface FAQComponentProps {
  faqs: FAQ[]
  className?: string
  onFAQClick?: (faq: FAQ) => void
}

export default function FAQComponent({ faqs, className, onFAQClick }: FAQComponentProps) {
  const [activeItem, setActiveItem] = useState<FAQ | null>(null)
  const ref = useRef<HTMLDivElement>(null) as React.RefObject<HTMLDivElement>
  useOnClickOutside(ref, () => setActiveItem(null))

  useEffect(() => {
    function onKeyDown(event: { key: string }) {
      if (event.key === "Escape") {
        setActiveItem(null)
      }
    }

    window.addEventListener("keydown", onKeyDown)
    return () => window.removeEventListener("keydown", onKeyDown)
  }, [])

  return (
    <>
      <AnimatePresence>
        {activeItem ? (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="bg-smooth-1000/10 /10 pointer-events-none fixed inset-0 z-50 bg-blend-luminosity backdrop-blur-xl"
          />
        ) : null}
      </AnimatePresence>
      <AnimatePresence>
        {activeItem ? (
          <>
            <div className="group fixed inset-0 z-50 grid place-items-center overflow-y-auto py-8">
              <motion.div
                className="bg-background flex h-fit w-[90%] max-w-2xl cursor-pointer flex-col items-start gap-4 overflow-hidden border p-6 shadow-xs"
                ref={ref}
                layoutId={`faqItem-${activeItem.question}`}
                style={{ borderRadius: 12 }}
              >
                <div className="flex w-full items-start gap-4">
                  <motion.div
                    layoutId={`faqIcon-${activeItem.question}`}
                    className="text-primary flex-shrink-0 text-2xl"
                  >
                    {activeItem.icon}
                  </motion.div>
                  <div className="flex grow flex-col gap-3">
                    <motion.div
                      className="text-foreground text-lg font-semibold"
                      layoutId={`faqQuestion-${activeItem.question}`}
                    >
                      {activeItem.question}
                    </motion.div>
                    <motion.p
                      layout
                      initial={{ opacity: 0 }}
                      animate={{ opacity: 1 }}
                      exit={{ opacity: 0, transition: { duration: 0.05 } }}
                      className="text-muted-foreground leading-relaxed"
                    >
                      {activeItem.answer}
                    </motion.p>
                  </div>
                </div>
              </motion.div>
            </div>
          </>
        ) : null}
      </AnimatePresence>
      <div className={`relative flex items-start ${className || ""}`}>
        <div className="relative flex w-full flex-col items-center gap-4 px-2">
          {faqs.map((faq, index) => (
            <motion.div
              layoutId={`faqItem-${faq.question}`}
              key={index}
              className="group bg-background flex w-full cursor-pointer flex-row items-center gap-4 border p-4 shadow-xs transition-shadow hover:shadow-md md:p-5"
              onClick={() => {
                setActiveItem(faq)
                if (onFAQClick) onFAQClick(faq)
              }}
              style={{ borderRadius: 8 }}
            >
              <motion.div layoutId={`faqIcon-${faq.question}`} className="text-primary flex-shrink-0 text-xl">
                {faq.icon}
              </motion.div>
              <div className="flex w-full flex-col items-start justify-between gap-1">
                <motion.div className="text-foreground font-medium" layoutId={`faqQuestion-${faq.question}`}>
                  {faq.question}
                </motion.div>
                <div className="text-muted-foreground line-clamp-1 text-sm">
                  {faq.answer}
                </div>
              </div>
            </motion.div>
          ))}
        </div>
      </div>
    </>
  )
}

"use server";

import { ActionResult } from "@/interfaces";

interface FeedbackData {
  email: string;
  feedback: string;
}

export async function submitFeedbackAction(data: FeedbackData): Promise<ActionResult> {
  try {
    const response = await fetch(
      `https://webhook.ottokit.com/ottokit/cb6ad4c0-5a3c-4777-9ac8-bf22011dd901?email=${encodeURIComponent(data.email)}&feedback=${encodeURIComponent(data.feedback)}`,
      {
        method: 'GET',
      }
    );

    if (!response.ok) {
      return {
        success: false,
        error: "Failed to submit feedback. Please try again.",
      };
    }

    return {
      success: true,
      data: { message: "Thank you for your feedback!" },
    };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred. Please try again.",
    };
  }
}
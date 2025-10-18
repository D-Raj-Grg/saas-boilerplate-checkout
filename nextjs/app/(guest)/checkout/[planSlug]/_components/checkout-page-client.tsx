"use client";

import { useState, useEffect, useRef } from "react";
import { Plan } from "@/actions/plans";
import { initiatePaymentAction, PaymentGateway } from "@/actions/payment";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { toast } from "sonner";
import { Loader2 } from "lucide-react";
import { useUser, useUserStore } from "@/stores/user-store";
import { formatCurrency, getAvailableGatewaysForCurrency } from "@/lib/currency";

interface CheckoutPageClientProps {
  plan: Plan;
  initialUserData?: any;
}

export function CheckoutPageClient({ plan, initialUserData }: CheckoutPageClientProps) {
  const user = useUser();
  const hasHydrated = useRef(false);

  // Hydrate user store from server-provided data on mount (for page refresh)
  useEffect(() => {
    if (initialUserData && !user && !hasHydrated.current) {
      hasHydrated.current = true;
      useUserStore.getState().setUserData(initialUserData);
    }
  }, [initialUserData, user]);
  const availableGateways = getAvailableGatewaysForCurrency(plan.currency);
  const [selectedGateway, setSelectedGateway] = useState<PaymentGateway>(
    availableGateways.includes("esewa") ? "esewa" : (availableGateways[0] as PaymentGateway)
  );
  const [isProcessing, setIsProcessing] = useState(false);

  // Guest checkout fields
  const [guestName, setGuestName] = useState("");
  const [guestEmail, setGuestEmail] = useState("");

  const handleCheckout = async () => {
    if (!selectedGateway) {
      toast.error("Please select a payment method");
      return;
    }

    // Validate guest fields if user is not authenticated
    if (!user) {
      if (!guestName.trim()) {
        toast.error("Please enter your name");
        return;
      }
      if (!guestEmail.trim()) {
        toast.error("Please enter your email");
        return;
      }
      // Basic email validation
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(guestEmail)) {
        toast.error("Please enter a valid email address");
        return;
      }
    }

    setIsProcessing(true);

    try {
      const result = await initiatePaymentAction({
        plan_slug: plan.slug,
        gateway: selectedGateway,
        guest_name: user ? undefined : guestName,
        guest_email: user ? undefined : guestEmail,
      });

      if (!result.success) {
        toast.error(result.error || "Failed to initiate payment");
        setIsProcessing(false);
        return;
      }

      // Handle eSewa - Build and submit form directly (matching reference implementation)
      if (result.gateway === "esewa" && result.formParams && result.paymentUrl) {
        toast.success("Redirecting to eSewa payment gateway...");

        console.log("eSewa Payment Data:", {
          paymentUrl: result.paymentUrl,
          formParams: result.formParams,
        });

        // Create form element
        const form = document.createElement("form");
        form.method = "POST";
        form.action = result.paymentUrl;

        // Add all form fields as hidden inputs
        Object.entries(result.formParams).forEach(([key, value]) => {
          const input = document.createElement("input");
          input.type = "hidden";
          input.name = key;
          input.value = String(value);
          form.appendChild(input);
          console.log(`Form field: ${key} = ${value}`);
        });

        // Append to body, submit, and remove
        document.body.appendChild(form);
        console.log("Submitting eSewa form...");
        form.submit();
        document.body.removeChild(form);

        // Note: Form submission will navigate away, so no need to reset isProcessing
        return;
      }

      // Redirect to payment gateway (works for Khalti, Mock, etc.)
      if (result.paymentUrl) {
        toast.success("Redirecting to payment gateway...");
        window.location.href = result.paymentUrl;
      }
    } catch (error) {
      console.error(error);
      toast.error("An unexpected error occurred");
      setIsProcessing(false);
    }
  };

  return (
    <div className="container max-w-6xl mx-auto py-8 px-4">
      <div className="mb-8">
        <h1 className="text-3xl font-bold mb-2">Complete Your Purchase</h1>
        <p className="text-muted-foreground">
          You&apos;re subscribing to the {plan.name} plan
        </p>
      </div>

      <div className="grid md:grid-cols-3 gap-6">
        {/* Plan Summary */}
        <Card>
          <CardHeader>
            <CardTitle>Plan Summary</CardTitle>
            <CardDescription>Review your selected plan</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div>
              <h3 className="font-semibold text-lg">{plan.name}</h3>
              <p className="text-sm text-muted-foreground">{plan.description}</p>
            </div>

            <div className="border-t pt-4">
              <div className="flex justify-between items-center">
                <span className="text-muted-foreground">Billing Cycle</span>
                <span className="font-medium capitalize">{plan.billing_cycle}</span>
              </div>
              <div className="flex justify-between items-center mt-2">
                <span className="text-muted-foreground">Amount</span>
                <span className="text-2xl font-bold">{formatCurrency(plan.price, plan.currency)}</span>
              </div>
            </div>

            <div className="border-t pt-4">
              <div className="flex justify-between items-center text-lg font-bold">
                <span>Total</span>
                <span>{formatCurrency(plan.price, plan.currency)}</span>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Guest Information (only show if not logged in) */}
        {!user && (
          <Card>
            <CardHeader>
              <CardTitle>Your Information</CardTitle>
              <CardDescription>We&apos;ll create an account for you</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="guest-name">Full Name</Label>
                <Input
                  id="guest-name"
                  type="text"
                  placeholder="John Doe"
                  value={guestName}
                  onChange={(e) => setGuestName(e.target.value)}
                  required
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="guest-email">Email Address</Label>
                <Input
                  id="guest-email"
                  type="email"
                  placeholder="john@example.com"
                  value={guestEmail}
                  onChange={(e) => setGuestEmail(e.target.value)}
                  required
                />
                <p className="text-xs text-muted-foreground">
                  You&apos;ll use this email to login after payment
                </p>
              </div>
            </CardContent>
          </Card>
        )}

        {/* Payment Method */}
        <Card className={!user ? "" : "md:col-span-2"}>
          <CardHeader>
            <CardTitle>Payment Method</CardTitle>
            <CardDescription>Select your preferred payment gateway</CardDescription>
          </CardHeader>
          <CardContent className="space-y-6">
            <RadioGroup
              value={selectedGateway}
              onValueChange={(value) => setSelectedGateway(value as PaymentGateway)}
            >
              {/* eSewa - NPR only */}
              {availableGateways.includes("esewa") && (
                <div className="flex items-center space-x-3 border rounded-lg p-4 cursor-pointer hover:bg-accent" onClick={() => setSelectedGateway("esewa")}>
                  <RadioGroupItem value="esewa" id="esewa" />
                  <Label htmlFor="esewa" className="flex-1 cursor-pointer">
                    <div className="font-medium">eSewa</div>
                    <div className="text-sm text-muted-foreground">Pay with eSewa wallet</div>
                  </Label>
                </div>
              )}

              {/* Khalti - NPR only */}
              {availableGateways.includes("khalti") && (
                <div className="flex items-center space-x-3 border rounded-lg p-4 cursor-pointer hover:bg-accent" onClick={() => setSelectedGateway("khalti")}>
                  <RadioGroupItem value="khalti" id="khalti" />
                  <Label htmlFor="khalti" className="flex-1 cursor-pointer">
                    <div className="font-medium">Khalti</div>
                    <div className="text-sm text-muted-foreground">Pay with Khalti wallet</div>
                  </Label>
                </div>
              )}

              {/* Stripe - International currencies */}
              {availableGateways.includes("stripe") && (
                <div className="flex items-center space-x-3 border rounded-lg p-4 cursor-pointer hover:bg-accent" onClick={() => setSelectedGateway("stripe")}>
                  <RadioGroupItem value="stripe" id="stripe" />
                  <Label htmlFor="stripe" className="flex-1 cursor-pointer">
                    <div className="font-medium">Stripe</div>
                    <div className="text-sm text-muted-foreground">Pay with credit/debit card</div>
                  </Label>
                </div>
              )}

              {/* Mock Processor - Development/Testing Only */}
              {process.env.NODE_ENV === "development" && availableGateways.includes("mock") && (
                <div className="flex items-center space-x-3 border rounded-lg p-4 cursor-pointer hover:bg-accent border-orange-200 bg-orange-50" onClick={() => setSelectedGateway("mock")}>
                  <RadioGroupItem value="mock" id="mock" />
                  <Label htmlFor="mock" className="flex-1 cursor-pointer">
                    <div className="font-medium text-orange-800">Mock Processor</div>
                    <div className="text-sm text-orange-600">Test payment (Development only)</div>
                  </Label>
                </div>
              )}
            </RadioGroup>

            <Button
              onClick={handleCheckout}
              disabled={isProcessing}
              className="w-full"
              size="lg"
            >
              {isProcessing ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  Processing...
                </>
              ) : (
                `Pay ${formatCurrency(plan.price, plan.currency)}`
              )}
            </Button>

            <div className="text-xs text-center text-muted-foreground">
              By completing this purchase, you agree to our Terms of Service and Privacy Policy.
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

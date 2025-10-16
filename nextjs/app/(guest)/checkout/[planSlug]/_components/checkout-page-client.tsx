"use client";

import { useState } from "react";
import { Plan } from "@/actions/plans";
import { initiatePaymentAction, PaymentGateway } from "@/actions/payment";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { toast } from "sonner";
import { Loader2 } from "lucide-react";
import { useUser } from "@/stores/user-store";

interface CheckoutPageClientProps {
  plan: Plan;
}

export function CheckoutPageClient({ plan }: CheckoutPageClientProps) {
  const user = useUser();
  const [selectedGateway, setSelectedGateway] = useState<PaymentGateway>("esewa");
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

      if (!result.success || !result.paymentUrl) {
        toast.error(result.error || "Failed to initiate payment");
        setIsProcessing(false);
        return;
      }

      // Redirect to payment gateway
      window.location.href = result.paymentUrl;
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
                <span className="text-2xl font-bold">NPR {plan.price}</span>
              </div>
            </div>

            <div className="border-t pt-4">
              <div className="flex justify-between items-center text-lg font-bold">
                <span>Total</span>
                <span>NPR {plan.price}</span>
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
              {/* eSewa */}
              <div className="flex items-center space-x-3 border rounded-lg p-4 cursor-pointer hover:bg-accent" onClick={() => setSelectedGateway("esewa")}>
                <RadioGroupItem value="esewa" id="esewa" />
                <Label htmlFor="esewa" className="flex-1 cursor-pointer">
                  <div className="font-medium">eSewa</div>
                  <div className="text-sm text-muted-foreground">Pay with eSewa wallet</div>
                </Label>
              </div>

              {/* Khalti */}
              <div className="flex items-center space-x-3 border rounded-lg p-4 cursor-pointer hover:bg-accent" onClick={() => setSelectedGateway("khalti")}>
                <RadioGroupItem value="khalti" id="khalti" />
                <Label htmlFor="khalti" className="flex-1 cursor-pointer">
                  <div className="font-medium">Khalti</div>
                  <div className="text-sm text-muted-foreground">Pay with Khalti wallet</div>
                </Label>
              </div>

              {/* Fonepay */}
              <div className="flex items-center space-x-3 border rounded-lg p-4 cursor-pointer hover:bg-accent" onClick={() => setSelectedGateway("fonepay")}>
                <RadioGroupItem value="fonepay" id="fonepay" />
                <Label htmlFor="fonepay" className="flex-1 cursor-pointer">
                  <div className="font-medium">Fonepay</div>
                  <div className="text-sm text-muted-foreground">Pay with Fonepay</div>
                </Label>
              </div>
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
                `Pay NPR ${plan.price}`
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

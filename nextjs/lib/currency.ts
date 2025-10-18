/**
 * Currency Configuration and Utilities
 */

export interface CurrencyConfig {
  code: string;
  symbol: string;
  name: string;
  decimalPlaces: number;
  symbolPosition: "before" | "after";
  thousandsSeparator: string;
  decimalSeparator: string;
}

export const SUPPORTED_CURRENCIES: Record<string, CurrencyConfig> = {
  NPR: {
    code: "NPR",
    symbol: "Rs.",
    name: "Nepalese Rupee",
    decimalPlaces: 2,
    symbolPosition: "before",
    thousandsSeparator: ",",
    decimalSeparator: ".",
  },
  USD: {
    code: "USD",
    symbol: "$",
    name: "US Dollar",
    decimalPlaces: 2,
    symbolPosition: "before",
    thousandsSeparator: ",",
    decimalSeparator: ".",
  },
  EUR: {
    code: "EUR",
    symbol: "€",
    name: "Euro",
    decimalPlaces: 2,
    symbolPosition: "before",
    thousandsSeparator: ",",
    decimalSeparator: ".",
  },
  GBP: {
    code: "GBP",
    symbol: "£",
    name: "British Pound",
    decimalPlaces: 2,
    symbolPosition: "before",
    thousandsSeparator: ",",
    decimalSeparator: ".",
  },
  INR: {
    code: "INR",
    symbol: "₹",
    name: "Indian Rupee",
    decimalPlaces: 2,
    symbolPosition: "before",
    thousandsSeparator: ",",
    decimalSeparator: ".",
  },
};

export const DEFAULT_CURRENCY = "NPR";

/**
 * Format a price with currency symbol
 */
export function formatCurrency(
  amount: number,
  currency: string = DEFAULT_CURRENCY,
  includeSymbol: boolean = true
): string {
  const config = SUPPORTED_CURRENCIES[currency] || SUPPORTED_CURRENCIES[DEFAULT_CURRENCY];

  const formatted = amount.toLocaleString("en-US", {
    minimumFractionDigits: config.decimalPlaces,
    maximumFractionDigits: config.decimalPlaces,
  });

  if (!includeSymbol) {
    return formatted;
  }

  return config.symbolPosition === "before"
    ? `${config.symbol} ${formatted}`
    : `${formatted} ${config.symbol}`;
}

/**
 * Get currency symbol
 */
export function getCurrencySymbol(currency: string = DEFAULT_CURRENCY): string {
  return SUPPORTED_CURRENCIES[currency]?.symbol || currency;
}

/**
 * Get currency name
 */
export function getCurrencyName(currency: string = DEFAULT_CURRENCY): string {
  return SUPPORTED_CURRENCIES[currency]?.name || currency;
}

/**
 * Check if currency is supported
 */
export function isSupportedCurrency(currency: string): boolean {
  return currency in SUPPORTED_CURRENCIES;
}

/**
 * Get available payment gateways for a currency
 */
export function getAvailableGatewaysForCurrency(currency: string): string[] {
  const gatewaySupport: Record<string, string[]> = {
    esewa: ["NPR"],
    khalti: ["NPR"],
    stripe: ["USD", "EUR", "GBP", "INR"],
    mock: ["NPR", "USD", "EUR", "GBP", "INR"], // Mock supports all for testing
  };

  const gateways: string[] = [];
  for (const [gateway, supportedCurrencies] of Object.entries(gatewaySupport)) {
    if (supportedCurrencies.includes(currency)) {
      gateways.push(gateway);
    }
  }

  return gateways;
}

/**
 * Check if a gateway supports a currency
 */
export function gatewaySupportsCurrency(gateway: string, currency: string): boolean {
  const availableGateways = getAvailableGatewaysForCurrency(currency);
  return availableGateways.includes(gateway);
}

/**
 * Format price for display based on plan data
 */
export function formatPlanPrice(plan: {
  price: number;
  currency: string;
  is_free?: boolean;
}): string {
  if (plan.is_free) {
    return "Free";
  }

  return formatCurrency(plan.price, plan.currency);
}

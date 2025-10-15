import { cn } from "@/lib/utils";

export const getPricingSlabParentClassName = (gridColsClassName = '') =>
    cn(
        `grid gap-y-8 justify-center`,
        `grid-cols-[repeat(auto-fit)]`,
        `md:w-full md:justify-center md:grid-cols-2 md:mx-auto `,
        'grid-cols-[repeat(auto-fit)]',
        `xs:grid-cols-[repeat(auto-fit,400px)] lg:grid-cols-[repeat(auto-fit,minmax(300px,400px))]`,
        gridColsClassName,
    );

export const joinPlanNames = (planNameList: string[] = []) => {
    if (planNameList.length === 0) return '';
    if (planNameList.length === 1) return planNameList[0];

    if (planNameList.length > 1) {
        const lastPlanName = planNameList.pop();
        return planNameList.join(', ') + ' & ' + lastPlanName;
    }
};


export const PricingPlanSlug = {
    Free: 'free',
    Starter: 'starter',
    Pro: 'pro',
    Business: 'business',
} as const;

export const getCTAText = (targetPlan: string, planSlug?: string) => {
    const planHierarchy = {
        [PricingPlanSlug.Free]: 0,
        [PricingPlanSlug.Starter]: 1,
        [PricingPlanSlug.Pro]: 2,
        [PricingPlanSlug.Business]: 3,
    };

    const currentLevel = planHierarchy[planSlug as keyof typeof planHierarchy] || 0;
    const targetLevel = planHierarchy[targetPlan as keyof typeof planHierarchy] || 0;

    if (currentLevel === 0) return 'Get Started';
    if (currentLevel < targetLevel) return 'Upgrade';
    if (currentLevel > targetLevel) return 'Downgrade';
    return 'Get Started';
};

export const PlanPrice = {
    starter: { monthly: 49, yearly: 490 },
    professional: { monthly: 99, yearly: 990, yearlyOld: 1188 },
    enterprise: { monthly: 0, yearly: 0 },
};

// Controllable line break for tooltips
const Br1em = () => <span className="block mt-5" />;

export const PRICING_TOOLTIPS = {
    EXPERIMENTS: (
        <div>
            Run multiple A/B tests simultaneously to optimize different parts of your website or app.
            Each experiment can test different variations to find the best performing option.
        </div>
    ),
    MONTHLY_VISITORS: (
        <div>
            Number of unique visitors that can be included in your experiments each month.
            Visitors are tracked anonymously and counted once per month regardless of how many experiments they participate in.
        </div>
    ),
    EXPERIMENT_DURATION: (
        <div>
            How long your experiments can run before automatically stopping.
            Longer durations allow for more data collection and statistical significance.
            <Br1em />
            You can manually stop experiments early or extend them as needed within your plan limits.
        </div>
    ),
    CONVERSION_TRACKING: (
        <div>
            Track custom conversion goals and events to measure experiment success.
            Set up multiple conversion goals per experiment to understand the full impact of your changes.
        </div>
    ),
    TEAM_MEMBERS: (
        <div>
            Add team members to your {process.env.NEXT_PUBLIC_APP_NAME} workspace with role-based access.
            Collaborate on experiments, share results, and manage testing strategy together.
        </div>
    ),
    API_ACCESS: (
        <div>
            Programmatically manage experiments, retrieve results, and integrate with your existing tools.
            Full REST API with comprehensive documentation for developers.
        </div>
    ),
    WHITE_LABEL: (
        <div>
            Remove {process.env.NEXT_PUBLIC_APP_NAME} branding from reports and dashboard for client presentations.
            Add your own company logo and colors for a professional, branded experience.
        </div>
    ),
    ADVANCED_TARGETING: (
        <div>
            Target specific user segments based on device, location, traffic source, and custom attributes.
            Create sophisticated audience rules to run experiments on exactly the right users.
        </div>
    ),
    MULTIVARIATE_TESTING: (
        <div>
            Test multiple variables simultaneously to understand how different elements interact.
            More powerful than simple A/B tests for complex optimization scenarios.
        </div>
    ),
    PRIORITY_SUPPORT: (
        <div>
            Get faster response times from our support team when you need help.
            Includes email support with guaranteed response times and priority queue access.
        </div>
    ),
    ADVANCED_ANALYTICS: (
        <div>
            Detailed statistical analysis, confidence intervals, and significance testing.
            Export data in multiple formats and integrate with your analytics stack.
        </div>
    ),
    EXPERIMENT_TEMPLATES: (
        <div>
            Pre-built experiment configurations for common optimization scenarios.
            Save time setting up new tests with proven templates and best practices.
        </div>
    ),
};

const FREE_PLAN_SLAB = {
    name: 'Free Trial 7 Days',
    slug: 'free',
    description: 'Perfect for getting started with A/B testing and exploring optimization opportunities.',
    costMonthly: 0,
    costAnnually: 0,
    ctaText: 'Get Started',
    currencySuperscript: true,
    features: {
        "What's included:": [
            {
                name: `500 Yearly Visitors`,
                tooltip: PRICING_TOOLTIPS.MONTHLY_VISITORS,
            },
            {
                name: `50 Team Members`,
                tooltip: PRICING_TOOLTIPS.TEAM_MEMBERS,
            },
            {
                name: `20 Workspaces`,
            },
            {
                name: `100 Experiments`,
                tooltip: PRICING_TOOLTIPS.EXPERIMENTS,
            },
            {
                name: `100 Active Experiments`,
                tooltip: PRICING_TOOLTIPS.EXPERIMENTS,
            },
            {
                name: '7 Days Data Retention',
            },
        ],
    },
};

const STARTER_PLAN_SLAB = {
    name: 'Starter',
    slug: 'starter',
    description: 'Ideal for small businesses and startups looking to optimize their conversion rates.',
    costMonthly: 29,
    costAnnually: 299,
    durationTag: '/year',
    billingText: `Billed annually. You pay $299 today.`,
    ctaText: 'Get Started',
    oldPrice: {
        monthly: 39,
        yearly: '399',
    },
    features: {
        "What's included:": [
            {
                name: `50,000 Yearly Visitors`,
                tooltip: PRICING_TOOLTIPS.MONTHLY_VISITORS,
            },
            {
                name: `50 Team Members`,
                tooltip: PRICING_TOOLTIPS.TEAM_MEMBERS,
            },
            {
                name: `20 Workspaces`,
            },
            {
                name: `100 Experiments`,
                tooltip: PRICING_TOOLTIPS.EXPERIMENTS,
            },
            {
                name: `100 Active Experiments`,
                tooltip: PRICING_TOOLTIPS.EXPERIMENTS,
            },
            {
                name: '60 Days Data Retention',
            },
        ],
    },
};

const PROFESSIONAL_PLAN_SLAB = {
    name: 'Pro',
    slug: 'pro',
    specialTag: 'Most Popular',
    isRecommended: true,
    description: 'Perfect for growing teams who need advanced testing capabilities and insights.',
    costMonthly: 49,
    costAnnually: 499,
    durationTag: '/year',
    billingText: `Billed annually. You pay $499 today.`,
    ctaText: 'Get Started',
    oldPrice: {
        monthly: 49,
        yearly: '499',
    },
    features: {
        "What's included:": [
            {
                name: `400,000 Yearly Visitors`,
                tooltip: PRICING_TOOLTIPS.MONTHLY_VISITORS,
            },
            {
                name: `50 Team Members`,
                tooltip: PRICING_TOOLTIPS.TEAM_MEMBERS,
            },
            {
                name: `20 Workspaces`,
            },
            {
                name: `100 Experiments`,
                tooltip: PRICING_TOOLTIPS.EXPERIMENTS,
            },
            {
                name: `100 Active Experiments`,
                tooltip: PRICING_TOOLTIPS.EXPERIMENTS,
            },
            {
                name: '60 Days Data Retention',
            },
        ],
    },
};

const ENTERPRISE_PLAN_SLAB = {
    name: 'Business',
    slug: 'business',
    description: 'For large organizations with advanced testing requirements.',
    costMonthly: 99,
    costAnnually: 999,
    durationTag: '/year',
    billingText: `Billed annually. You pay $999 today.`,
    ctaText: 'Get Started',
    oldPrice: {
        monthly: 99,
        yearly: '999',
    },
    features: {
        "What's included:": [
            {
                name: `1,000,000 Yearly Visitors`,
                tooltip: PRICING_TOOLTIPS.MONTHLY_VISITORS,
            },
            {
                name: `50 Team Members`,
                tooltip: PRICING_TOOLTIPS.TEAM_MEMBERS,
            },
            {
                name: `20 Workspaces`,
            },
            {
                name: `100 Experiments`,
                tooltip: PRICING_TOOLTIPS.EXPERIMENTS,
            },
            {
                name: `100 Active Experiments`,
                tooltip: PRICING_TOOLTIPS.EXPERIMENTS,
            },
            {
                name: '60 Days Data Retention',
            },
        ],
    },
};

export const PRICING_SLABS = {
    FREE_PLAN_SLAB,
    STARTER_PLAN_SLAB,
    PROFESSIONAL_PLAN_SLAB,
    ENTERPRISE_PLAN_SLAB,
};
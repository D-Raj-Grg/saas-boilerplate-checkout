"use client";

import { cn } from "@/lib/utils";
import { CheckIcon } from "lucide-react";

interface ComparisonTableProps {
    className?: string;
    checkingAuth?: boolean;
    handleCheckout?: (plan: any) => void;
    loadingCheckoutFor?: string | null;
}

export function ComparisonTable({
    className,
}: ComparisonTableProps) {
    const plans = [
        // {
        //     id: 'free',
        //     name: 'Free Trial',
        //     priceYear: '$0',
        //     priceMonth: '$0',
        //     refundGuarantee: '-',
        //     organization: 1,
        //     teamMembers: 50,
        //     workspaces: 20,
        //     experiments: 100,
        //     activeExperiments: 100,
        //     variationsPerExperiment: 10,
        //     eventsRetention: '7 Days',
        //     sessionRecordingsRetention: '7 Days',
        //     heatmapRetention: '7 Days',
        //     dataDeletion: 'After 30 Days',
        //     analyticsEDD: true,
        //     analyticsSureCart: true,
        //     analyticsWooCommerce: true,
        //     heatmap: true,
        //     sessionRecordings: true,
        // },
        {
            id: 'starter',
            name: 'Starter',
            priceYear: '$299',
            priceMonth: '$29',
            refundGuarantee: '7 Days',
            organization: 1,
            teamMembers: 50,
            workspaces: 20,
            experiments: 100,
            activeExperiments: 100,
            variationsPerExperiment: 10,
            eventsRetention: '60 Days',
            sessionRecordingsRetention: '60 Days',
            heatmapRetention: '60 Days',
            dataDeletion: 'After 30 Days',
            analyticsEDD: true,
            analyticsSureCart: true,
            analyticsWooCommerce: true,
            heatmap: true,
            sessionRecordings: true,
        },
        {
            id: 'pro',
            name: 'Pro',
            priceYear: '$499',
            priceMonth: '$49',
            refundGuarantee: '7 Days',
            organization: 1,
            teamMembers: 50,
            workspaces: 20,
            experiments: 100,
            activeExperiments: 100,
            variationsPerExperiment: 10,
            eventsRetention: '60 Days',
            sessionRecordingsRetention: '60 Days',
            heatmapRetention: '60 Days',
            dataDeletion: 'After 30 Days',
            analyticsEDD: true,
            analyticsSureCart: true,
            analyticsWooCommerce: true,
            heatmap: true,
            sessionRecordings: true,
        },
        {
            id: 'business',
            name: 'Business',
            priceYear: '$999',
            priceMonth: '$99',
            refundGuarantee: '7 Days',
            organization: 1,
            teamMembers: 50,
            workspaces: 20,
            experiments: 100,
            activeExperiments: 100,
            variationsPerExperiment: 10,
            eventsRetention: '60 Days',
            sessionRecordingsRetention: '60 Days',
            heatmapRetention: '60 Days',
            dataDeletion: 'After 30 Days',
            analyticsEDD: true,
            analyticsSureCart: true,
            analyticsWooCommerce: true,
            heatmap: true,
            sessionRecordings: true,
        }
    ];

    const features = [
        {
            name: 'Organizations',
            description: 'Create and manage multiple organizations with full administrative control',
        },
        {
            name: 'Team Members',
            description: 'Collaborate with up to 50 team members per organization',
        },
        {
            name: 'Workspaces',
            description: 'Organize projects across up to 20 separate workspaces per organization',
        },
        {
            name: 'Advanced Analytics',
            description: 'Track user behavior, conversions, and engagement metrics',
        },
        {
            name: 'Custom Domains',
            description: 'Connect your own custom domain for a branded experience',
        },
        {
            name: 'API Access',
            description: 'Full REST API access for integrations and custom workflows',
        },
        {
            name: 'Priority Support',
            description: 'Get priority email support with faster response times',
        },
        {
            name: 'Data Export',
            description: 'Export all your data in standard formats (CSV, JSON)',
        },
        {
            name: 'Email Notifications',
            description: 'Automated email alerts for important events and activities',
        },
        {
            name: 'Role-Based Permissions',
            description: 'Granular access control with custom roles and permissions',
        },
        {
            name: 'Audit Logs',
            description: 'Complete activity logs for compliance and security monitoring',
        },
        {
            name: 'SSO Integration',
            description: 'Single Sign-On support for enterprise authentication',
        },
        {
            name: '7-Day Money-Back Guarantee',
            description: 'Full refund available within 7 days of purchase, no questions asked',
        },
    ];

    return (
        <div className={cn("my-12 w-full", className)}>
            <div className="overflow-x-auto -mx-4 md:-mx-6 lg:-mx-8 px-4 md:px-6 lg:px-8">
                <table className="min-w-full">
                    {/* Header with Plan Names and Pricing */}
                    <thead>
                        <tr className="border-b border-gray-200">
                            <th className="py-4 md:py-6 text-left w-2/5 min-w-[160px] md:min-w-[200px]"></th>
                            {plans.map((plan) => (
                                <th
                                    key={plan.id}
                                    className="py-4 md:py-6 text-center min-w-[120px] md:min-w-[140px]"
                                >
                                    <div className="flex flex-col gap-1 md:gap-2">
                                        <div className="text-base md:text-xl font-bold text-gray-900">
                                            {plan.name}
                                        </div>
                                        <div className="text-2xl md:text-4xl font-bold text-primary">
                                            {plan.priceYear}
                                            <span className="text-sm md:text-lg text-gray-500 font-normal">
                                                /year
                                            </span>
                                        </div>
                                    </div>
                                </th>
                            ))}
                        </tr>
                    </thead>

                    {/* Feature Rows */}
                    <tbody className="bg-white">
                        {features.map((feature, index) => (
                            <tr
                                key={index}
                                className="border-b border-gray-100"
                            >
                                <td className="py-4 md:py-6 text-left pr-2 md:pr-4">
                                    <div className="flex flex-col gap-0.5 md:gap-1">
                                        <div className="text-sm md:text-base font-semibold text-gray-900">
                                            {feature.name}
                                        </div>
                                        <div className="text-xs md:text-sm text-gray-500 hidden sm:block">
                                            {feature.description}
                                        </div>
                                    </div>
                                </td>
                                {plans.map((plan) => (
                                    <td
                                        key={plan.id}
                                        className="py-4 md:py-6 text-center"
                                    >
                                        <div className="flex justify-center items-center">

                                            <div className="bg-primary/5 rounded-full p-1.5 md:p-2 w-fit">
                                                <CheckIcon className="w-4 h-4 md:w-5 md:h-5 text-primary mx-auto" />
                                            </div>
                                        </div>
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

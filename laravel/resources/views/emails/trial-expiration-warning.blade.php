@extends('emails.layout')

@section("title", "Your Trial is Expiring Soon")

@section('preview-text')
Your {{ $organization->name }} trial is expiring in {{ $daysRemaining }} day{{ $daysRemaining != 1 ? 's' : '' }}. Upgrade now to continue using {{ config('app.name') }}.
@endsection

@section('main-content')
    <table role="presentation" class="table-content" cellspacing="0" width="600" cellpadding="0" border="0" align="center">
        <tr>
            <td height="48" style="height: 48px; line-height: 48px;"></td>
        </tr>

        <!-- Warning Header -->
        <tr>
            <td style="padding: 0px 48px;" align="center">
                <div style="background-color: #FEF3C7; border-radius: 50%; width: 64px; height: 64px; display: inline-flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                    <span style="font-size: 32px;">⏰</span>
                </div>
                <h1 style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #000000; font-size: 32px; font-weight: 700; line-height: 1.2; margin: 0 0 16px 0; text-align: center;">
                    Your Trial is Expiring Soon
                </h1>
                <p style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #6B7280; font-size: 18px; line-height: 1.6; margin: 0; text-align: center; font-weight: 400;">
                    Only {{ $daysRemaining }} day{{ $daysRemaining != 1 ? 's' : '' }} left on your free trial
                </p>
            </td>
        </tr>

        <tr>
            <td height="40" style="height: 40px; line-height: 40px;"></td>
        </tr>

        <!-- Main Content -->
        <tr>
            <td style="padding: 0px 48px;" align="left">
                <p style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 24px 0; font-weight: 400;">
                    Hi {{ $user->name ?? 'there' }},
                </p>
                <p style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 24px 0; font-weight: 400;">
                    Your free trial for <strong>{{ $organization->name }}</strong> will expire in <strong>{{ $daysRemaining }} day{{ $daysRemaining != 1 ? 's' : '' }}</strong>.
                </p>
                <p style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 32px 0; font-weight: 400;">
                    After your trial expires, your access will be limited and you won't be able to use premium features until you upgrade to a paid plan.
                </p>
            </td>
        </tr>

        <!-- Warning Box -->
        <tr>
            <td style="padding: 0px 48px;">
                <table cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td style="padding: 24px; background-color: #FEF3C7; border-radius: 12px; border-left: 4px solid #F59E0B;">
                            <h3 style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #92400E; font-size: 16px; font-weight: 600; margin: 0 0 8px 0;">
                                <span aria-hidden="true">⚠️</span> What happens when my trial expires?
                            </h3>
                            <p style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #78350F; font-size: 15px; line-height: 1.6; margin: 0;">
                                Your access to premium features will be restricted, and you won't be able to use certain capabilities until you upgrade.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td height="32" style="height: 32px; line-height: 32px;"></td>
        </tr>

        <!-- CTA Button -->
        <tr>
            <td style="padding: 0px 48px;" align="center">
                <table cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td>
                            <a href="{{ config('app.frontend_url', config('app.url')) }}/settings/billing"
                               style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
                                      background-color: #005F5A;
                                      border-radius: 16px;
                                      color: #FFFFFF;
                                      display: inline-block;
                                      font-size: 16px;
                                      font-weight: 600;
                                      text-decoration: none;
                                      line-height: 1.2;
                                      padding: 16px 32px;
                                      text-align: center;
                                      min-width: 200px;
                                      transition: all 0.2s ease;"
                               target="_blank">
                                Upgrade Now
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td height="32" style="height: 32px; line-height: 32px;"></td>
        </tr>

        <!-- Benefits Section -->
        <tr>
            <td style="padding: 0px 48px;">
                <table cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td style="padding: 24px; background-color: #F0F9FF; border-radius: 12px; border-left: 4px solid #22c55e;">
                            <h3 style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #000000; font-size: 18px; font-weight: 600; margin: 0 0 12px 0;">
                                <span aria-hidden="true">🚀</span> What You'll Keep with a Paid Plan
                            </h3>
                            <ul style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #374151; font-size: 15px; line-height: 1.6; margin: 0; padding-left: 20px;">
                                <li style="margin-bottom: 8px;">All your data and projects</li>
                                <li style="margin-bottom: 8px;">Unlimited projects</li>
                                <li style="margin-bottom: 8px;">Advanced analytics and insights</li>
                                <li style="margin-bottom: 0;">Priority support from our team</li>
                            </ul>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td height="32" style="height: 32px; line-height: 32px;"></td>
        </tr>

        <!-- Closing -->
        <tr>
            <td style="padding: 0px 48px;" align="left">
                <p style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 24px 0; font-weight: 400;">
                    If you have any questions about our plans or need help upgrading, please don't hesitate to reach out. We're here to help!
                </p>

                <p style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #374151; font-size: 16px; line-height: 1.6; margin: 0; font-weight: 400;">
                    Best regards,<br>
                    <span style="font-weight: 600; color: #000000;">The {{ config('app.name') }} Team</span>
                </p>
            </td>
        </tr>

        <tr>
            <td height="48" style="height: 48px; line-height: 48px;"></td>
        </tr>
    </table>
@endsection

@extends('emails.layout')

@section("title", "Welcome to {{ config('app.name') }}")

@section('preview-text')
Welcome to {{ config('app.name') }}! Get started with your account and explore all features.
@endsection

@section('main-content')
    <table role="presentation" class="table-content" cellspacing="0" width="600" cellpadding="0" border="0" align="center">
        <tr>
            <td height="48" style="height: 48px; line-height: 48px;"></td>
        </tr>
        
        <!-- Welcome Header -->
        <tr>
            <td style="padding: 0px 48px;" align="center">
                <h1 style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #000000; font-size: 32px; font-weight: 700; line-height: 1.2; margin: 0 0 16px 0; text-align: center;">
                    Welcome to {{ config('app.name') }}!
                </h1>
                <p style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #6B7280; font-size: 18px; line-height: 1.6; margin: 0; text-align: center; font-weight: 400;">
                    Get started with your new account
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
                <p style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 32px 0; font-weight: 400;">
                    Thank you for joining {{ config('app.name') }}! Your account is ready and you can start exploring all the features we have to offer.
                </p>
            </td>
        </tr>
        
        <!-- CTA Button -->
        <tr>
            <td style="padding: 0px 48px;" align="center">
                <table cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td>
                            <a href="{{ config('app.frontend_url', config('app.url')) }}/dashboard"
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
                                Go to Dashboard
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <tr>
            <td height="40" style="height: 40px; line-height: 40px;"></td>
        </tr>
        
        <!-- Feature Highlights -->
        <tr>
            <td style="padding: 0px 48px;">
                <table cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td style="padding: 24px; background-color: #F0F9FF; border-radius: 12px; border-left: 4px solid #22c55e;">
                            <h3 style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #000000; font-size: 18px; font-weight: 600; margin: 0 0 12px 0;">
                                <span aria-hidden="true">🚀</span> Quick Start Tips
                            </h3>
                            <ul style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #374151; font-size: 15px; line-height: 1.6; margin: 0; padding-left: 20px;">
                                <li style="margin-bottom: 8px;">Create your organization and invite team members</li>
                                <li style="margin-bottom: 8px;">Set up workspaces for your projects</li>
                                <li style="margin-bottom: 8px;">Manage permissions and roles for your team</li>
                                <li style="margin-bottom: 0;">Connect your WordPress site or other integrations</li>
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
                    If you have any questions or need assistance getting started, our team is here to help. Just reply to this email!
                </p>
                
                <p style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #374151; font-size: 16px; line-height: 1.6; margin: 0; font-weight: 400;">
                    Welcome aboard!<br>
                    <span style="font-weight: 600; color: #000000;">The {{ config('app.name') }} Team</span>
                </p>
            </td>
        </tr>
        
        <tr>
            <td height="48" style="height: 48px; line-height: 48px;"></td>
        </tr>
    </table>
@endsection
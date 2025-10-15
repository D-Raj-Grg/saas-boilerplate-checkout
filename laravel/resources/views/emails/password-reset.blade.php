@extends('emails.layout')

@section("title", "Reset Your Password - {{ config('app.name') }}")

@section('preview-text')
Reset your {{ config('app.name') }} password securely. This link will expire at {{ $expiresAt }}.
@endsection

@section('main-content')
    <table role="presentation" class="table-content" cellspacing="0" width="600" cellpadding="0" border="0" align="center">
        <tr>
            <td height="48" style="height: 48px; line-height: 48px;"></td>
        </tr>
        
        <!-- Header -->
        <tr>
            <td style="padding: 0px 48px;" align="center">
              
                <h1 style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #000000; font-size: 28px; font-weight: 700; line-height: 1.2; margin: 0 0 16px 0; text-align: center;">
                    Reset Your Password
                </h1>
                <p class="brand-text-light" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: var(--color-text-light, #525252); font-size: 16px; line-height: 1.6; margin: 0; text-align: center; font-weight: 400;">
                    We received a request to reset your account password
                </p>
            </td>
        </tr>
        
        <tr>
            <td height="40" style="height: 40px; line-height: 40px;"></td>
        </tr>
        
        <!-- Main Content -->
        <tr>
            <td style="padding: 0px 48px;" align="left">
                <p class="brand-text" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: var(--color-text, #0A0A0A); font-size: 16px; line-height: 1.6; margin: 0 0 24px 0; font-weight: 400;">
                    Hello,
                </p>
                <p class="brand-text" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: var(--color-text, #0A0A0A); font-size: 16px; line-height: 1.6; margin: 0 0 32px 0; font-weight: 400;">
                    Someone requested a password reset for your {{ config('app.name') }} account. If this was you, click the button below to create a new secure password.
                </p>
            </td>
        </tr>
        
        <!-- CTA Button -->
        <tr>
            <td style="padding: 0px 48px;" align="center">
                <table cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td>
                            <a href="{{ $resetUrl }}"
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
                                Reset My Password
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <tr>
            <td height="40" style="height: 40px; line-height: 40px;"></td>
        </tr>
        
        <!-- Security Notice -->
        <tr>
            <td style="padding: 0px 48px;">
                <table cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td style="padding: 20px 24px; background-color: #FEF3C7; border-radius: 12px; border-left: 4px solid #F59E0B;">
                            <h3 style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #000000; font-size: 16px; font-weight: 600; margin: 0 0 12px 0;">
                                <span aria-hidden="true">🔒</span> Security Notice
                            </h3>
                            <p class="brand-text" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: var(--color-text, #0A0A0A); font-size: 14px; line-height: 1.6; margin: 0 0 12px 0;">
                                This password reset link will expire at <strong>{{ $expiresAt }}</strong>.
                            </p>
                            <p class="brand-text" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: var(--color-text, #0A0A0A); font-size: 14px; line-height: 1.6; margin: 0;">
                                If you didn't request this password reset, please ignore this email and your password will remain unchanged.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <tr>
            <td height="32" style="height: 32px; line-height: 32px;"></td>
        </tr>
        
        <!-- Security Tips -->
        <tr>
            <td style="padding: 0px 48px;">
                <h3 class="brand-text-heading" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: var(--color-heading, #0A0A0A); font-size: 18px; font-weight: 600; margin: 0 0 16px 0;">
                    <span aria-hidden="true">🛡️</span> Keep Your Account Secure
                </h3>
                <ul class="brand-text-light" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: var(--color-text-light, #525252); font-size: 15px; line-height: 1.6; margin: 0 0 24px 0; padding-left: 20px;">
                    <li style="margin-bottom: 8px;">Use a strong, unique password with at least 12 characters</li>
                    <li style="margin-bottom: 8px;">Include uppercase, lowercase, numbers, and symbols</li>
                    <li style="margin-bottom: 0;">Never share your password with anyone</li>
                </ul>
            </td>
        </tr>
        
        <!-- Closing -->
        <tr>
            <td style="padding: 0px 48px;" align="left">
                <p class="brand-text" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: var(--color-text, #0A0A0A); font-size: 16px; line-height: 1.6; margin: 0 0 24px 0; font-weight: 400;">
                    If you have any questions or concerns about your account security, please contact our support team immediately.
                </p>

                <p class="brand-text" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: var(--color-text, #0A0A0A); font-size: 16px; line-height: 1.6; margin: 0; font-weight: 400;">
                    Stay secure!<br>
                    <span class="brand-text-heading" style="font-weight: 600; color: var(--color-heading, #0A0A0A);">The {{ config('app.name') }} Security Team</span>
                </p>
            </td>
        </tr>
        
        <tr>
            <td height="48" style="height: 48px; line-height: 48px;"></td>
        </tr>
    </table>
@endsection
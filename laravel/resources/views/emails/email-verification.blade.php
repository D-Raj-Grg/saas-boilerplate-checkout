@extends('emails.layout')

@section("title", "Verify Your Email - {{ config('app.name') }}")

@section('preview-text')
Verify your email address to activate your {{ config('app.name') }} account. This link will expire in {{ $expiresIn }} hours.
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
                    Verify Your Email
                </h1>
                <p class="brand-text-light" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: var(--color-text-light, #525252); font-size: 16px; line-height: 1.6; margin: 0; text-align: center; font-weight: 400;">
                    One more step to activate your {{ config('app.name') }} account
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
                    Hi {{ $user->name ?? 'there' }},
                </p>
                <p class="brand-text" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: var(--color-text, #0A0A0A); font-size: 16px; line-height: 1.6; margin: 0 0 32px 0; font-weight: 400;">
                    Welcome to {{ config('app.name') }}! To complete your registration and start using your account, please verify your email address by clicking the button below.
                </p>
            </td>
        </tr>
        
        <!-- CTA Button -->
        <tr>
            <td style="padding: 0px 48px;" align="center">
                <table cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td>
                            <a href="{{ $verificationUrl }}"
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
                                Verify My Email
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <tr>
            <td height="40" style="height: 40px; line-height: 40px;"></td>
        </tr>
        
        <!-- Info Box -->
        <tr>
            <td style="padding: 0px 48px;">
                <table cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td style="padding: 20px 24px; background-color: #FEF3C7; border-radius: 12px; border-left: 4px solid #F59E0B;">
                            <h3 style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #000000; font-size: 16px; font-weight: 600; margin: 0 0 12px 0;">
                                <span aria-hidden="true">⏰</span> Time Sensitive
                            </h3>
                            <p class="brand-text" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: var(--color-text, #0A0A0A); font-size: 14px; line-height: 1.6; margin: 0 0 8px 0;">
                                This verification link will expire in <strong>{{ $expiresIn }} hours</strong>.
                            </p>
                            <p class="brand-text" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: var(--color-text, #0A0A0A); font-size: 14px; line-height: 1.6; margin: 0;">
                                If you didn't create this account, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <tr>
            <td height="32" style="height: 32px; line-height: 32px;"></td>
        </tr>
        
        <!-- What's Next -->
        <tr>
            <td style="padding: 0px 48px;">
                <h3 class="brand-text-heading" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: var(--color-heading, #0A0A0A); font-size: 18px; font-weight: 600; margin: 0 0 16px 0;">
                    <span aria-hidden="true">🚀</span> What's Next?
                </h3>
                <p class="brand-text-light" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: var(--color-text-light, #525252); font-size: 15px; line-height: 1.6; margin: 0 0 16px 0;">
                    Once your email is verified, you'll be able to:
                </p>
                <ul class="brand-text-light" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: var(--color-text-light, #525252); font-size: 15px; line-height: 1.6; margin: 0 0 24px 0; padding-left: 20px;">
                    <li style="margin-bottom: 8px;">Manage your account and projects</li>
                    <li style="margin-bottom: 8px;">Track user assignments and conversion rates</li>
                    <li style="margin-bottom: 8px;">Access detailed analytics and reports</li>
                    <li style="margin-bottom: 0;">Collaborate with your team members</li>
                </ul>
            </td>
        </tr>
        
        <!-- Closing -->
        <tr>
            <td style="padding: 0px 48px;" align="left">
                <p class="brand-text" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: var(--color-text, #0A0A0A); font-size: 16px; line-height: 1.6; margin: 0 0 24px 0; font-weight: 400;">
                    If you have any questions or need assistance, our support team is ready to help. Simply reply to this email!
                </p>
                
                <p class="brand-text" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: var(--color-text, #0A0A0A); font-size: 16px; line-height: 1.6; margin: 0; font-weight: 400;">
                    Welcome aboard!<br>
                    <span class="brand-text-heading" style="font-weight: 600; color: var(--color-heading, #0A0A0A);">The {{ config('app.name') }} Team</span>
                </p>
            </td>
        </tr>
        
        <tr>
            <td height="48" style="height: 48px; line-height: 48px;"></td>
        </tr>
    </table>
@endsection
@extends('emails.layout')

@section("title", "Team Invitation - {{ config('app.name') }}")

@section('preview-text')
{{ $inviter->name }} invited you to join {{ $organization->name }} on {{ config('app.name') }}.
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
                    You're Invited!
                </h1>
                <p class="brand-text-light" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: var(--color-text-light, #525252); font-size: 16px; line-height: 1.6; margin: 0; text-align: center; font-weight: 400;">
                    Join your team on {{ config('app.name') }}
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
                    <strong class="brand-text-heading" style="color: var(--color-heading, #0A0A0A);">{{ $inviter->name }}</strong> has invited you to join <strong style="color: var(--color-primary, #005F5A);">{{ $organization->name }}</strong> on {{ config('app.name') }}.
                </p>
                
                @if($invitation->message ?? false)
                <table cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 0 0 32px 0;">
                    <tr>
                        <td class="brand-info-box" style="padding: 20px 24px; background-color: var(--color-accent, #F5F5F5); border-radius: 8px; border-left: 4px solid var(--color-primary, #005F5A);">
                            <p class="brand-text" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: var(--color-text, #0A0A0A); font-size: 15px; line-height: 1.6; margin: 0 0 12px 0; font-style: italic;">
                                "{{ $invitation->message }}"
                            </p>
                            <p class="brand-text-light" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: var(--color-text-light, #525252); font-size: 14px; margin: 0; font-weight: 500;">
                                — {{ $inviter->name }}
                            </p>
                        </td>
                    </tr>
                </table>
                @endif
            </td>
        </tr>
        
        <!-- CTA Button -->
        <tr>
            <td style="padding: 0px 48px;" align="center">
                <table cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td>
                            <a href="{{ $acceptUrl }}"
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
                                Accept Invitation
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <tr>
            <td height="40" style="height: 40px; line-height: 40px;"></td>
        </tr>
        
        <!-- Benefits -->
        <tr>
            <td style="padding: 0px 48px;">
                <h3 class="brand-text-heading" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: var(--color-heading, #0A0A0A); font-size: 18px; font-weight: 600; margin: 0 0 16px 0;">
                    <span aria-hidden="true">🎯</span> What You'll Get Access To
                </h3>
                <ul class="brand-text-light" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: var(--color-text-light, #525252); font-size: 15px; line-height: 1.6; margin: 0 0 24px 0; padding-left: 20px;">
                    <li style="margin-bottom: 8px;">Collaborate with {{ $organization->name }}</li>
                    <li style="margin-bottom: 8px;">Access shared resources and analytics</li>
                    <li style="margin-bottom: 8px;">Create and manage variants together</li>
                    <li style="margin-bottom: 0;">View real-time conversion tracking and results</li>
                </ul>
            </td>
        </tr>
        
        <!-- Urgency Notice -->
        <tr>
            <td style="padding: 0px 48px;">
                <table cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td style="padding: 16px 20px; background-color: #FEF3C7; border-radius: 12px; border-left: 4px solid #F59E0B;">
                            <p style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #374151; font-size: 14px; line-height: 1.6; margin: 0; font-weight: 500;">
                                <span aria-hidden="true">⏰</span> This invitation will expire in <strong>7 days</strong>. Accept it now to join your team!
                            </p>
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
                <p class="brand-text" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: var(--color-text, #0A0A0A); font-size: 16px; line-height: 1.6; margin: 0 0 24px 0; font-weight: 400;">
                    If you have any questions about this invitation or need help getting started, our support team is here to assist you.
                </p>
                
                <p class="brand-text" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: var(--color-text, #0A0A0A); font-size: 16px; line-height: 1.6; margin: 0; font-weight: 400;">
                    Looking forward to having you on the team!<br>
                    <span class="brand-text-heading" style="font-weight: 600; color: var(--color-heading, #0A0A0A);">The {{ config('app.name') }} Team</span>
                </p>
            </td>
        </tr>
        
        <tr>
            <td height="48" style="height: 48px; line-height: 48px;"></td>
        </tr>
    </table>
@endsection
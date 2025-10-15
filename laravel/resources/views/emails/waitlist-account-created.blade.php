@extends('emails.layout')

@section("title", "Your {{ config('app.name') }} Account is Ready!")

@section('preview-text')
Great news! Your waitlist account has been created. Login now with your credentials.
@endsection

@section('main-content')
    <table role="presentation" class="table-content" cellspacing="0" width="600" cellpadding="0" border="0" align="center">
        <tr>
            <td height="48" style="height: 48px; line-height: 48px;"></td>
        </tr>
        
        <!-- Welcome Header -->
        <tr>
            <td style="padding: 0px 48px;" align="center">
                <h1 style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #1e293b; font-size: 32px; font-weight: 700; line-height: 1.2; margin: 0 0 16px 0; text-align: center;">
                    🎉 Your Account is Ready!
                </h1>
                <p style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #64748b; font-size: 18px; line-height: 1.6; margin: 0; text-align: center; font-weight: 400;">
                    Welcome to {{ config('app.name') }} - Get started today
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
                    Hi {{ $waitlist->first_name ?? 'there' }},
                </p>
                <p style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 32px 0; font-weight: 400;">
                    Great news! You joined our waitlist and your {{ config('app.name') }} account has now been created automatically. You can now access all the powerful features we have to offer.
                </p>
            </td>
        </tr>
        
        <!-- Credentials Box -->
        <tr>
            <td style="padding: 0px 48px;">
                <table cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td style="padding: 24px; background-color: #f8fafc; border-radius: 8px; border-left: 4px solid #7033ff;">
                            <h3 style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #1e293b; font-size: 18px; font-weight: 600; margin: 0 0 16px 0;">
                                🔑 Your Login Credentials
                            </h3>
                            <p style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #475569; font-size: 15px; line-height: 1.6; margin: 0 0 12px 0;">
                                <strong>Email:</strong> {{ $waitlist->email }}
                            </p>
                            <p style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #475569; font-size: 15px; line-height: 1.6; margin: 0;">
                                <strong>Password:</strong> 
                                <code style="background-color: #ffffff; padding: 4px 8px; border-radius: 4px; font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace; color: #1e293b; font-size: 14px; border: 1px solid #e2e8f0;">{{ $password }}</code>
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
                            <a href="{{ config('app.frontend_url') }}/login" 
                               style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; 
                                      background-color: #7033ff; 
                                      border-radius: 6px; 
                                      color: #ffffff; 
                                      display: inline-block; 
                                      font-size: 16px; 
                                      font-weight: 600; 
                                      text-decoration: none; 
                                      line-height: 1.2; 
                                      padding: 14px 32px; 
                                      text-align: center; 
                                      min-width: 200px;"
                               target="_blank">
                                Login to Your Account
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <tr>
            <td height="32" style="height: 32px; line-height: 32px;"></td>
        </tr>
        
        <!-- Security Notice -->
        <tr>
            <td style="padding: 0px 48px;">
                <table cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td style="padding: 20px; background-color: #fef3cd; border-radius: 8px; border-left: 4px solid #f59e0b;">
                            <p style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #92400e; font-size: 15px; line-height: 1.6; margin: 0; font-weight: 500;">
                                <span aria-hidden="true">⚠️</span> <strong>Important:</strong> Please change your password after your first login for security.
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
                <p style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 24px 0; font-weight: 400;">
                    Thank you for joining our waitlist! We're excited to have you on board and can't wait to see what you'll accomplish.
                </p>
                
                <p style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; color: #374151; font-size: 16px; line-height: 1.6; margin: 0; font-weight: 400;">
                    If you have any questions or need assistance getting started, just reply to this email!<br>
                    <span style="font-weight: 600; color: #1e293b;">The {{ config('app.name') }} Team</span>
                </p>
            </td>
        </tr>
        
        <tr>
            <td height="48" style="height: 48px; line-height: 48px;"></td>
        </tr>
    </table>
@endsection
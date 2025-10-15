<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="x-apple-disable-message-reformatting">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>@yield('title',config('app.name'))</title>
  <!-- Removed Google Fonts for privacy/security - using system fonts -->
  <style>
    /* Enhanced system font stack for modern appearance */
    @font-face {
      font-family: 'Email-Safe';
      src: local('Inter'), local('SF Pro Display'), local('Segoe UI'), local('Roboto'), sans-serif;
    }
  </style>
  <!--[if mso]>
    <style>
        table {
            border-collapse: collapse;
        }
        .o_col {
            float: left;
        }
    </style>
    <xml>
        <o:OfficeDocumentSettings>
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml>
    <![endif]-->
  <style type="text/css">
    body,
    html {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
    }
    body {
      margin: 0;
      padding: 0;
    }
    body,
    table,
    td,
    p,
    a,
    li {
      -webkit-text-size-adjust: 100%;
      -ms-text-size-adjust: 100%;
    }
    table td {
      border-collapse: collapse;
    }
    table {
      border-spacing: 0;
      border-collapse: collapse;
    }
    p,
    a,
    li,
    td,
    blockquote {
      mso-line-height-rule: exactly;
    }
    p,
    a,
    li,
    td,
    body,
    table,
    blockquote {
      -ms-text-size-adjust: 100%;
      -webkit-text-size-adjust: 100%;
    }
    img,
    a img {
      border: 0;
      outline: none;
      text-decoration: none;
    }
    img {
      -ms-interpolation-mode: bicubic;
    }
    * img[tabindex="0"]+div {
      display: none !important;
    }
    a[href^=tel],
    a[href^=sms],
    a[href^=mailto],
    a[href^=date] {
      color: inherit;
      cursor: pointer;
      text-decoration: none;
    }
    a[x-apple-data-detectors] {
      color: inherit !important;
      text-decoration: none !important;
      font-size: inherit !important;
      font-family: inherit !important;
      font-weight: inherit !important;
      line-height: inherit !important;
    }
    .text-center {
      text-align: center !important;
    }


    /* Adaptive Logo */
    .logo-container {
      display: inline-flex !important;
      align-items: center !important;
      gap: 12px !important;
    }
    .logo-symbol {
      width: auto !important;
      height: 40px !important;
      flex-shrink: 0 !important;
    }
    .logo-text {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif !important;
      font-size: 28px !important;
      font-weight: 600 !important;
      margin: 0 !important;
      text-decoration: none !important;
    }
    /* Logo text in dark wrapper (header area) */
    .logo-text-dark-bg {
      color: #FFFFFF !important;
    }
    /* Logo text in light content areas */
    .logo-text-light-bg {
      color: #000000 !important;
    }


    /* Email client compatibility - consistent light background */
    .email-body {
      background-color: #FAFAFA !important;
    }
    .email-content {
      background-color: #FFFFFF !important;
      border-radius: 16px !important;
      box-shadow: 0 4px 16px rgba(0,0,0,0.12) !important;
    }


    @media screen {
      body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
      }
      .table-content p a,
      .table-content a {
        word-break: break-word;
      }
    }
    @media only screen and (max-width: 640px) {
      .table-content p a,
      .table-content a {
        word-break: break-word;
      }
      .table-content {
        width: 100% !important;
        min-width: 10% !important;
        margin: 0 !important;
        float: none !important;
      }
      .td-content-wrapper {
        padding-bottom: 0px !important;
        padding-left: 15px !important;
        padding-right: 15px !important;
        padding-top: 0px !important;
      }
      .footer-content {
        padding-bottom: 0px !important;
        padding-left: 5px !important;
        padding-right: 5px !important;
        padding-top: 0px !important;
      }
      .button a {
        display: block !important;
        width: auto !important;
      }
      .gap-half-10 {
        height: 5px !important;
      }
      .gap-half-20 {
        height: 10px !important;
      }
      .gap-half-30 {
        height: 15px !important;
      }
      .gap-half-40 {
        height: 20px !important;
      }
      .gap-half-60 {
        height: 30px !important;
      }
      body {
        margin: 0px !important;
        padding: 0px !important;
      }
      body,
      table,
      td,
      p,
      a,
      li,
      blockquote {
        -webkit-text-size-adjust: none !important;
      }
      .footer-content p {
        text-align: center !important;
      }
      .logo-container {
        gap: 8px !important;
      }
      .logo-symbol {
        width: auto !important;
        height: 30px !important;
      }
      .logo-text {
        font-size: 22px !important;
      }

      #footer-wrapper {
        float: none !important;
        margin: 0 auto;
      }
      #footer-wrapper .footer-content {
        padding-bottom: 5px !important;
        padding-top: 5px !important;
      }
      #footer-wrapper .footer-content>span {
        width: 10px !important;
        min-width: 10px !important;
      }
      #footer-wrapper .footer-content>span img {
        width: 10px !important;
        min-width: 10px !important;
      }
    }
  </style>

</head>
<body
  style="padding: 0; margin: 0; -webkit-font-smoothing:antialiased; background-color:#FAFAFA; -webkit-text-size-adjust:none;">
  <div style="display:none;">@yield('preview-text')</div>
  <img src="" alt="" width="1" height="1"
    style="display:block; height:0px; width:0px; max-width:0px; max-height:0px; overflow:hidden;" border="0">
  <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#FAFAFA" dir="ltr" background="" class="email-body">
    <tr>
      <td align="center">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td align="center" style="padding: 48px 20px 32px 20px;">
              <!-- {{ config('app.name') }} Logo -->
              <a href="{{ config('app.frontend_url') }}" target="_blank" style="text-decoration: none;">
                <div class="logo-container" style="display: inline-flex; align-items: center; gap: 12px;">
                  <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }} Symbol" class="logo-symbol" style="width: auto; height: 40px; flex-shrink: 0;">
                </div>
              </a>
            </td>
          </tr>
        </table>
        
        <!-- White Content Area -->
        <table width="600" border="0" cellspacing="0" cellpadding="0" class="table-content email-content" style="background-color: #FFFFFF; border-radius: 16px; box-shadow: 0 4px 16px rgba(0,0,0,0.12); margin: 0 auto;">
          <tr>
            <td>
              @yield('main-content')
            </td>
          </tr>
        </table>
        
        <!-- Footer outside white area -->
        <table width="600" border="0" cellspacing="0" cellpadding="0" class="table-content" style="margin: 0 auto;">
          <tr>
            <td style="padding: 40px 20px;">
              @include('emails.partials.footer')
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>

<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $subject ?? config('app.name') }}</title>
  <style>
    /* ===== VARIABLES & RESET ===== */
    :root {
      --primary: #30B59B;
      --primary-light: #8FD7C9;
      --primary-soft: #E8F6F3;
      --white: #FEFEFE;
      --gray-light: #F9FBFB;
      --gray-medium: #E8EFED;
      --gray-dark: #5A6E69;
      --gray-text: #3A4A46;
      --dark: #1C2B28;
      --radius-lg: 20px;
      --radius-md: 12px;
      --radius-sm: 8px;
      --shadow-soft: 0 10px 40px rgba(48, 181, 155, 0.08);
      --shadow-card: 0 4px 20px rgba(0, 0, 0, 0.04);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      margin: 0;
      padding: 40px 20px;
      background-color: var(--gray-light);
      font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', Roboto, sans-serif;
      color: var(--gray-text);
      line-height: 1.6;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }

    /* ===== CONTAINER ===== */
    .email-wrapper {
      max-width: 580px;
      margin: 0 auto;
    }

    .container {
      background-color: var(--white);
      border-radius: var(--radius-lg);
      overflow: hidden;
      box-shadow: var(--shadow-soft);
      border: 1px solid rgba(143, 215, 201, 0.15);
    }

    /* ===== HEADER ===== */
    .header {
      background: linear-gradient(165deg, var(--primary) 0%, rgba(48, 181, 155, 0.95) 100%);
      padding: 20px 30px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }

    .header-decoration {
      position: absolute;
      width: 200px;
      height: 200px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.05);
      top: -100px;
      right: -100px;
    }

    .header-decoration:nth-child(2) {
      width: 150px;
      height: 150px;
      top: 50%;
      left: -75px;
      background: rgba(255, 255, 255, 0.03);
    }

    .logo {
      position: relative;
      z-index: 2;
    }

    .logo h1 {
      margin: 0;
      font-size: 30px;
      font-weight: 600;
      color: var(--white);
      letter-spacing: -0.3px;
    }

    .logo-subtitle {
      font-size: 14px;
      color: rgba(254, 254, 254, 0.85);
      margin-top: 6px;
      font-weight: 500;
      letter-spacing: 0.5px;
    }

    /* ===== CONTENT ===== */
    .content {
      padding: 15px 35px;
    }

    .greeting {
      font-size: 20px;
      color: var(--dark);
      margin-bottom: 12px;
      font-weight: 600;
    }

    .greeting strong {
      color: var(--primary);
      font-weight: 700;
    }

    .message {
      font-size: 16px;
      line-height: 1.7;
      color: var(--gray-text);
      margin-bottom: 24px;
    }

    /* ===== OTP BOX ===== */
    .otp-container {
      margin: 40px 0;
      position: relative;
    }

    .otp-box {
      background: linear-gradient(145deg, var(--white) 0%, var(--primary-soft) 100%);
      border-radius: var(--radius-md);
      padding: 32px 24px;
      border: 1px solid rgba(48, 181, 155, 0.2);
      box-shadow: var(--shadow-card);
      position: relative;
      overflow: hidden;
    }

    .otp-box::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
    }

    .otp-label {
      display: block;
      font-size: 13px;
      color: var(--gray-dark);
      margin-bottom: 16px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      text-align: center;
    }

    .otp-code {
      font-size: 42px;
      font-weight: 700;
      letter-spacing: 8px;
      color: var(--dark);
      font-family: 'SF Mono', 'Roboto Mono', Monaco, monospace;
      text-align: center;
      margin: 16px 0;
      line-height: 1.2;
    }

    .otp-code span {
      display: inline-block;
      min-width: 40px;
      text-align: center;
    }

    .otp-expiry {
      text-align: center;
      font-size: 14px;
      color: var(--gray-dark);
      margin-top: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .otp-expiry strong {
      color: var(--primary);
    }

    /* ===== CTA BUTTONS ===== */
    .cta-container {
      text-align: center;
      margin: 36px 0 32px;
    }

    .cta-button {
      display: inline-block;
      background-color: var(--primary);
      color: var(--white) !important;
      padding: 16px 36px;
      border-radius: var(--radius-md);
      font-weight: 600;
      font-size: 15px;
      text-decoration: none;
      text-align: center;
      transition: all 0.25s ease;
      border: none;
      cursor: pointer;
      position: relative;
      overflow: hidden;
    }

    .cta-button::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0));
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .cta-button:hover {
      background-color: #2AA38B;
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(48, 181, 155, 0.2);
    }

    .cta-button:hover::after {
      opacity: 1;
    }

    .cta-button:active {
      transform: translateY(0);
    }

    /* ===== SECURITY NOTE ===== */
    .security-note {
      background: rgba(248, 252, 251, 0.8);
      border-radius: var(--radius-sm);
      padding: 24px;
      margin-top: 32px;
      border: 1px solid rgba(143, 215, 201, 0.3);
      position: relative;
    }

    .security-note::before {
      content: '🔒';
      position: absolute;
      top: 20px;
      left: 20px;
      font-size: 18px;
    }

    .security-note p {
      color: var(--gray-text);
      font-size: 14px;
      margin: 0 0 5px 30px;
      line-height: 1.6;
    }

    .security-note p:first-child {
      font-weight: 600;
      color: var(--primary);
    }

    /* ===== FEATURE LIST ===== */
    .feature-list {
      margin: 32px 0;
    }

    .feature-item {
      display: flex;
      align-items: flex-start;
      margin-bottom: 16px;
      padding-left: 8px;
    }

    .feature-icon {
      color: var(--primary);
      margin-right: 12px;
      font-size: 18px;
      min-width: 24px;
    }

    .feature-text {
      color: var(--gray-text);
      font-size: 15px;
    }

    /* ===== SIGNATURE ===== */
    .signature {
      margin-top: 20px;
      padding-top: 15px;
      border-top: 1px solid var(--gray-medium);
    }

    .signature p {
      color: var(--gray-text);
      line-height: 1.7;
    }

    .founder {
      margin-top: 24px;
      padding: 20px;
      background: var(--primary-soft);
      border-radius: var(--radius-md);
      border-left: 4px solid var(--primary-light);
    }

    .founder-name {
      font-weight: 700;
      color: var(--dark);
      margin-bottom: 4px;
    }

    .founder-title {
      font-size: 14px;
      color: var(--primary);
      margin-bottom: 12px;
    }

    .founder-quote {
      font-style: italic;
      color: var(--gray-dark);
      font-size: 15px;
      line-height: 1.6;
    }


        .founder-avatar {
            border-radius: 50%;
            background: var(--primary);
            padding: 6px;
            font-weight: 700;
            color: var(--white);
            font-size: 12px;
            margin-bottom: 20px;
        }


    /* ===== FOOTER ===== */
    .footer {
      background: linear-gradient(to bottom, var(--primary-soft) 0%, rgba(232, 246, 243, 0.8) 100%);
      padding: 12px 25px;
      text-align: center;
    }

    .social-links {
      margin-bottom: 24px;
      display: flex;
      justify-content: center;
      gap: 20px;
    }

    .social-icon {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: var(--white);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary);
      text-decoration: none;
      transition: all 0.3s ease;
      border: 1px solid rgba(48, 181, 155, 0.15);
    }

    .social-icon:hover {
      background: var(--primary);
      color: var(--white);
      transform: translateY(-2px);
    }

    .footer-links {
      margin-bottom: 20px;
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 16px;
    }

    .footer-link {
      color: var(--gray-dark);
      text-decoration: none;
      font-size: 13px;
      transition: color 0.3s ease;
    }

    .footer-link:hover {
      color: var(--primary);
    }

    .copyright {
      font-size: 12px;
      color: var(--gray-dark);
      margin-top: 5px;
      opacity: 0.8;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 600px) {
      body {
        padding: 20px 12px;
        background-color: var(--white);
      }

      .header {
        padding: 28px 20px;
        border-radius: var(--radius-md) var(--radius-md) 0 0;
      }


      .otp-code {
        font-size: 34px;
        letter-spacing: 6px;
      }

      .cta-button {
        padding: 14px 28px;
        width: 100%;
        max-width: 280px;
      }

      .footer {
        padding: 24px 20px;
      }

      .footer-links {
        flex-direction: column;
        gap: 12px;
      }

      .social-links {
        gap: 16px;
      }
    }

    /* ===== ANIMATIONS ===== */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(15px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .container {
      animation: fadeInUp 0.4s ease-out;
    }

    .otp-box {
      animation: fadeInUp 0.5s ease-out 0.1s both;
    }

    /* ===== UTILITY ===== */
    .text-center {
      text-align: center;
    }

    .mt-32 {
      margin-top: 32px;
    }

    .mb-24 {
      margin-bottom: 24px;
    }
  </style>
</head>

<body>
  <div class="email-wrapper">
    <div class="container">

      <div class="header">
        <div class="header-decoration"></div>
        <div class="header-decoration"></div>
        <div class="logo">
          <h1>{{ config('app.name') }}</h1>
          <div class="logo-subtitle">Finance Intelligente</div>
        </div>
      </div>

      <div class="content">
        @yield('content')
      </div>

      <div class="footer">
        <!-- <div class="social-links">
          <a href="https://twitter.com/leyinvest" class="social-icon" aria-label="Twitter">🐦</a>
          <a href="https://linkedin.com/company/leyinvest" class="social-icon" aria-label="LinkedIn">👔</a>
          <a href="https://instagram.com/leyinvest" class="social-icon" aria-label="Instagram">📸</a>
          <a href="mailto:support@leyinvest.com" class="social-icon" aria-label="Email">✉️</a>
        </div> -->


        <div class="copyright">

          <span class="founder-avatar">LY</span>
          <div class="founder-info">
            <h4>Louis-Emmanuel YAO</h4>
            <p>Fondateur & CEO de LeyInvest</p>
          </div>

          <p>© {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.</p>
          <p>Cet email a été envoyé à {{ $user->email ?? 'vous' }}</p>
        </div>
      </div>
    </div>
  </div>
</body>

</html>

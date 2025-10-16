<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <style>
        /* Base styles */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .email-header {
            background: linear-gradient(135deg, #6e8efb, #a777e3);
            padding: 30px 20px;
            text-align: center;
            color: white;
        }

        .email-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }

        .email-body {
            padding: 40px 30px;
        }

        .email-body h2 {
            margin-top: 0;
            font-size: 22px;
            color: #2d3748;
        }

        .email-body p {
            line-height: 1.6;
            margin-bottom: 20px;
            color: #4a5568;
        }

        .verification-code {
            background-color: #f7fafc;
            border: 1px dashed #cbd5e0;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
            text-align: center;
        }

        .code {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 8px;
            color: #2d3748;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
        }

        .expiry-notice {
            font-size: 14px;
            color: #718096;
            margin-top: 10px;
        }

        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #6e8efb, #a777e3);
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
            text-align: center;
        }

        .email-footer {
            padding: 20px 30px;
            background-color: #f7fafc;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 14px;
            color: #718096;
        }

        .email-footer a {
            color: #6e8efb;
            text-decoration: none;
        }

        .divider {
            height: 1px;
            background-color: #e2e8f0;
            margin: 20px 0;
        }

        .support-info {
            margin-top: 20px;
            font-size: 14px;
        }

        /* Responsive styles */
        @media (max-width: 600px) {
            .email-container {
                margin: 10px;
                border-radius: 8px;
            }

            .email-body {
                padding: 30px 20px;
            }

            .code {
                font-size: 24px;
                letter-spacing: 6px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>Verify Your Email</h1>
        </div>

        <div class="email-body">
            <h2>Hello [User Name],</h2>
            <p>Thank you for signing up! To complete your registration, please use the verification code below:</p>

            <div class="verification-code">
                <p style="margin-top: 0;">Your verification code:</p>
                <div class="code">8 4 3 9 2 1</div>
                <p class="expiry-notice">This code will expire in 30 minutes</p>
            </div>

            <p>Enter this code on the verification page to complete your account setup.</p>

            <div style="text-align: center;">
                <a href="#" class="cta-button">Verify Email Address</a>
            </div>

            <p>If the button doesn't work, you can also enter the code manually on the verification page.</p>

            <div class="divider"></div>

            <p>If you didn't create an account with us, please ignore this email.</p>
        </div>

        <div class="email-footer">
            <p>Need help? <a href="mailto:support@example.com">Contact our support team</a></p>
            <p>© 2023 Your Company Name. All rights reserved.</p>
            <div class="support-info">
                <p>This email was sent to [user@example.com].</p>
                <p>Your Company Name, 123 Business Ave, Suite 100, City, State 12345</p>
            </div>
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote #{{ $quote->quote_number }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9fafb;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 32px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 2px solid #e5e7eb;
        }
        .header h1 {
            color: #111827;
            font-size: 28px;
            margin: 0 0 8px 0;
        }
        .header p {
            color: #6b7280;
            font-size: 14px;
            margin: 0;
        }
        .content {
            margin-bottom: 32px;
        }
        .content p {
            margin: 0 0 16px 0;
            color: #374151;
        }
        .info-box {
            background-color: #f3f4f6;
            border-left: 4px solid #4f46e5;
            padding: 16px;
            margin: 24px 0;
            border-radius: 4px;
        }
        .info-box h3 {
            margin: 0 0 8px 0;
            color: #111827;
            font-size: 16px;
        }
        .info-box p {
            margin: 4px 0;
            font-size: 14px;
            color: #4b5563;
        }
        .info-box strong {
            color: #111827;
        }
        .cta-button {
            display: inline-block;
            background-color: #4f46e5;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            margin: 24px 0;
        }
        .cta-button:hover {
            background-color: #4338ca;
        }
        .button-container {
            text-align: center;
        }
        .footer {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
        .footer p {
            margin: 4px 0;
        }
        .highlight {
            background-color: #fef3c7;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 New Quote Available</h1>
            <p>Quote #{{ $quote->quote_number }}</p>
        </div>

        <div class="content">
            <p>Dear {{ $customer->name }},</p>

            <p>Thank you for your interest! We're pleased to present you with <strong>{{ $optionsCount }} {{ $optionsCount === 1 ? 'option' : 'options' }}</strong> for your consideration.</p>

            <p>We've carefully prepared multiple options to give you the flexibility to choose what best fits your needs and budget.</p>

            <div class="info-box">
                <h3>Quote Details</h3>
                <p><strong>Quote Number:</strong> {{ $quote->quote_number }}</p>
                <p><strong>Number of Options:</strong> {{ $optionsCount }}</p>
                @if($quote->expires_at)
                    <p><strong>Valid Until:</strong> {{ $quote->expires_at->format('F d, Y') }}</p>
                @endif
            </div>

            <p><strong>What's Next?</strong></p>
            <p>Click the button below to review all available options. You can compare prices, delivery times, and features to make the best decision for your needs.</p>

            <div class="button-container">
                <a href="{{ $publicUrl }}" class="cta-button">
                    View Quote & Select Option
                </a>
            </div>

            <p style="font-size: 14px; color: #6b7280;">
                <em>Or copy and paste this link into your browser:</em><br>
                <a href="{{ $publicUrl }}" style="color: #4f46e5; word-break: break-all;">{{ $publicUrl }}</a>
            </p>

            <p>Once you've selected your preferred option, our team will be notified immediately and will reach out to finalize the details.</p>

            <p>If you have any questions or need additional information about any of the options, please don't hesitate to contact us.</p>

            <p style="margin-top: 24px;">
                Best regards,<br>
                <strong>{{ config('app.name') }} Team</strong>
            </p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p>This email was sent to {{ $customer->email ?? 'you' }} regarding Quote #{{ $quote->quote_number }}</p>
        </div>
    </div>
</body>
</html>

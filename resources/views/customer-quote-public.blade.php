<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Quote - Coming Soon</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
        }
        .icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        h1 {
            color: #2d3748;
            font-size: 32px;
            margin-bottom: 16px;
            font-weight: 700;
        }
        p {
            color: #718096;
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 12px;
        }
        .token {
            background: #f7fafc;
            border: 2px dashed #cbd5e0;
            border-radius: 8px;
            padding: 16px;
            margin: 30px 0;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            color: #4a5568;
            word-break: break-all;
        }
        .badge {
            display: inline-block;
            background: #fbbf24;
            color: #78350f;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 20px;
        }
        .info {
            background: #e0e7ff;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-top: 30px;
            text-align: left;
            border-radius: 8px;
        }
        .info h3 {
            color: #4c51bf;
            font-size: 16px;
            margin-bottom: 10px;
        }
        .info ul {
            color: #5a67d8;
            font-size: 14px;
            line-height: 1.8;
            padding-left: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🚧</div>
        <h1>Customer Quote Portal</h1>
        <p>The public customer quote interface is currently under development.</p>
        <p><strong>Phase 3</strong> will implement this feature with a professional, customer-friendly interface.</p>
        
        <div class="badge">Coming Soon - Phase 3</div>
        
        <div class="token">
            <strong>Your Quote Token:</strong><br>
            {{ $token }}
        </div>
        
        <div class="info">
            <h3>📋 What's Coming in Phase 3:</h3>
            <ul>
                <li>View all quote options with detailed pricing</li>
                <li>Compare different supplier options side-by-side</li>
                <li>Select your preferred option</li>
                <li>Professional, mobile-responsive design</li>
                <li>No login required - access via secure token</li>
            </ul>
        </div>
        
        <p style="margin-top: 30px; font-size: 14px; color: #a0aec0;">
            This is a placeholder page. The full functionality will be available after Phase 3 implementation.
        </p>
    </div>
</body>
</html>

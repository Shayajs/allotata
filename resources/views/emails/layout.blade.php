<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Allo Tata' }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #22c55e;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            background: linear-gradient(90deg, #22c55e, #f97316);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .content {
            margin-bottom: 30px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(90deg, #22c55e, #f97316);
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e5e5;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .info-box {
            background-color: #f0f9ff;
            border-left: 4px solid #22c55e;
            padding: 15px;
            margin: 20px 0;
        }
        .warning-box {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">Allo Tata</div>
        </div>

        <div class="content">
            @yield('content')
        </div>

        <div class="footer">
            <p>Cet email a été envoyé par <strong>Allo Tata</strong></p>
            <p>Si vous ne souhaitez plus recevoir ces emails, vous pouvez modifier vos préférences dans votre compte.</p>
            <p style="margin-top: 10px;">
                <a href="{{ url('/') }}" style="color: #22c55e; text-decoration: none;">Allo Tata</a> |
                <a href="{{ route('legal.confidentialite') }}" style="color: #666; text-decoration: none;">Confidentialité</a>
            </p>
        </div>
    </div>
</body>
</html>

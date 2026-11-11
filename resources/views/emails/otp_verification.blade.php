<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>EmbryoCase – {{ $purposeText }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #FFFFFF;
            color: #000000;
            margin: 0;
            padding: 40px 20px;
            font-size: 14px;
            line-height: 1.6;
        }
        .container {
            max-width: 520px;
            margin: 0 auto;
            border: 1px solid #E5E5E5;
            padding: 30px;
        }
        .header {
            border-bottom: 1px solid #E5E5E5;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .brand {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #000000;
        }
        .tagline {
            font-size: 11px;
            color: #737373;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .otp-box {
            background-color: #FAFAFA;
            border: 1px solid #E5E5E5;
            padding: 20px;
            text-align: center;
            margin: 25px 0;
        }
        .otp-code {
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 8px;
            color: #000000;
        }
        .footer {
            border-top: 1px solid #E5E5E5;
            padding-top: 15px;
            margin-top: 30px;
            font-size: 11px;
            color: #737373;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="brand">EMBRYOCASE</div>
            <div class="tagline">Embryo Data &amp; Research Management System</div>
        </div>

        <p>You have requested a <strong>{{ strtolower($purposeText) }}</strong> code for your laboratory account.</p>

        <p>Your verification code is:</p>

        <div class="otp-box">
            <div class="otp-code">{{ $otp }}</div>
        </div>

        <p>This code expires in <strong>{{ $expiryMinutes }} minutes</strong>.</p>
        <p>Do not share this code with anyone.</p>
        <p>If you did not initiate this authentication request, please report it immediately to your laboratory administrator.</p>

        <div class="footer">
            CONFIDENTIAL MEDICAL &amp; RESEARCH COMMUNICATION<br>
            EmbryoCase Laboratory Information Management System
        </div>
    </div>
</body>
</html>


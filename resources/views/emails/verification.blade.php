<!DOCTYPE html>
<html>
<head>
    <title>Email Verification Code</title>
</head>
<body>
    <h1>Email Verification</h1>
    <p>Hello {{ $user->name }},</p>
    <p>Thank you for registering. Use the verification code below to verify your email address:</p>
    
    <div style="background-color: #f5f5f5; padding: 20px; text-align: center; margin: 20px 0; border-radius: 5px;">
        <h2 style="font-size: 32px; letter-spacing: 10px; color: #2196F3;">
            {{ $verificationCode }}
        </h2>
    </div>
    
    <p>Enter this 6-digit code on the verification page to complete your registration.</p>
    
    <p><strong>Code expires in {{ $expiryMinutes }} minutes.</strong></p>
    
    <p>If you didn't create an account, you can safely ignore this email.</p>
</body>
</html>
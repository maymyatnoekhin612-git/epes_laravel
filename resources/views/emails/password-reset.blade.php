<!DOCTYPE html>
<html>
<head>
    <title>Password Reset Code</title>
</head>
<body>
    <h1>Password Reset Request</h1>
    <p>Hello {{ $user->name }},</p>
    <p>You requested to reset your password. Use the code below to verify your identity:</p>
    
    <div style="background-color: #f5f5f5; padding: 20px; text-align: center; margin: 20px 0; border-radius: 5px;">
        <h2 style="font-size: 32px; letter-spacing: 10px; color: #2196F3;">
            {{ $resetCode }}
        </h2>
    </div>
    
    <p>Enter this 6-digit code on the password reset page to set a new password.</p>
    
    <p><strong>Code expires in {{ $expiryMinutes }} minutes.</strong></p>
    
    <p>If you didn't request a password reset, you can safely ignore this email.</p>
    
    <p><small>For security reasons, this code will expire after {{ $expiryMinutes }} minutes 
    and can only be used 3 times.</small></p>
</body>
</html>
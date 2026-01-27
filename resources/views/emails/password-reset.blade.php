<!DOCTYPE html>
<html>
<head>
    <title>Password Reset Request</title>
</head>
<body>
    <h1>Password Reset Request</h1>
    <p>Hello {{ $user->name }},</p>
    <p>You requested to reset your password. Click the button below to set a new password:</p>
    
    <a href="{{ $resetUrl }}" 
       style="background-color: #2196F3; color: white; padding: 14px 20px; text-align: center; text-decoration: none; display: inline-block; border-radius: 5px;">
        Reset Password
    </a>
    
    <p>Or copy and paste this link in your browser:</p>
    <p>{{ $resetUrl }}</p>
    
    <p>If you didn't request a password reset, you can safely ignore this email.</p>
    <p>This reset link will expire in 1 hour.</p>
</body>
</html>
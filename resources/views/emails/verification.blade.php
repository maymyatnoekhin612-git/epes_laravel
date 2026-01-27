<!DOCTYPE html>
<html>
<head>
    <title>Verify Your Email</title>
</head>
<body>
    <h1>Welcome to English Proficiency Test System!</h1>
    <p>Hello {{ $user->name }},</p>
    <p>Thank you for registering. Please click the button below to verify your email address:</p>
    
    <a href="{{ $verificationUrl }}" 
       style="background-color: #4CAF50; color: white; padding: 14px 20px; text-align: center; text-decoration: none; display: inline-block; border-radius: 5px;">
        Verify Email Address
    </a>
    
    <p>If you didn't create an account, you can safely ignore this email.</p>
    <p>This verification link will expire in 24 hours.</p>
</body>
</html>
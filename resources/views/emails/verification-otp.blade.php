<!DOCTYPE html>
<html lang="en">
    <body style="font-family: Arial, sans-serif; color: #222; line-height: 1.6;">
        <p>Hello {{ $user->name }},</p>
        <p>{{ $purpose === 'login' ? 'Your MUBCAA sign-in code is:' : 'Your MUBCAA email verification code is:' }}</p>
        <p style="font-size: 28px; font-weight: 700; letter-spacing: 0.18em;">{{ $code }}</p>
        <p>This code will expire in 15 minutes.</p>
        <p>If you did not request this, you can ignore this email.</p>
    </body>
</html>

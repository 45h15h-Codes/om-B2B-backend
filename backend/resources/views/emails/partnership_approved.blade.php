<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your OM Gems Partner Account Has Been Approved</title>
    <style>
        body { font-family: 'Plus Jakarta Sans', Arial, sans-serif; background-color: #f4f7f9; color: #2d3748; margin: 0; padding: 20px; }
        .card { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 40px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
        .header { font-size: 20px; font-weight: 700; color: #108bb6; border-bottom: 1px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 30px; }
        .content { font-size: 15px; line-height: 1.6; margin-bottom: 30px; }
        .info-box { background-color: #f7fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 24px; font-size: 14.5px; }
        .btn { display: inline-block; background-color: #108bb6; color: #ffffff !important; font-weight: 700; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-size: 14px; text-align: center; }
        .btn:hover { background-color: #0b7094; }
        .footer { font-size: 12px; color: #718096; margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 20px; }
        .security-note { font-size: 12px; color: #a0aec0; margin-top: 15px; font-style: italic; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">Your OM Gems Partner Account Has Been Approved</div>
        <div class="content">
            <p>Dear {{ $name }},</p>
            <p>Welcome to OM Gems! We are pleased to notify you that your partnership request has been reviewed and approved.</p>
            <p>A new administrator account has been successfully provisioned for you. Please click the button below to set up your password and access the partner dashboard:</p>
            
            <div class="info-box">
                <strong>Registered Email:</strong> {{ $email }}
            </div>
            
            <p style="text-align: center; margin: 30px 0;">
                <a href="{{ $setupPasswordUrl }}" class="btn">
                    Set Your Password
                </a>
            </p>
            
            <p class="security-note">Please note: For security reasons, this setup link is temporary and will expire according to the system password reset expiration configuration. If the link expires, you can request a new password reset link from the login page.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} OM Gems. All rights reserved.
        </div>
    </div>
</body>
</html>

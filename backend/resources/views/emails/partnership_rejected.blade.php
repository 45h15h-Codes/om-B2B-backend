<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OM Gems Partnership Request Update</title>
    <style>
        body { font-family: 'Plus Jakarta Sans', Arial, sans-serif; background-color: #f4f7f9; color: #2d3748; margin: 0; padding: 20px; }
        .card { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 40px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
        .header { font-size: 20px; font-weight: 700; color: #e53e3e; border-bottom: 1px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 30px; }
        .content { font-size: 15px; line-height: 1.6; margin-bottom: 30px; }
        .notes-box { background-color: #fff5f5; border: 1px solid #fed7d7; border-radius: 8px; padding: 20px; margin-bottom: 24px; font-style: italic; color: #e53e3e; }
        .footer { font-size: 12px; color: #718096; margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">OM Gems Partnership Request Update</div>
        <div class="content">
            <p>Dear {{ $name }},</p>
            <p>Thank you for your interest in partnering with OM Gems. We appreciate the time you took to submit your partnership request.</p>
            <p>After careful consideration, we regret to inform you that we are unable to approve your partnership request at this time.</p>
            
            @if(!empty($notes))
                <div class="notes-box">
                    <strong>Rejection Reason / Notes:</strong><br>
                    {{ $notes }}
                </div>
            @endif
            
            <p>We wish you all the best in your business endeavors.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} OM Gems. All rights reserved.
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OM Gems - Set Up Password</title>
    <!-- Google Fonts & Font Awesome -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #108bb6;
            --primary-hover: #0d7296;
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            --glass-bg: rgba(30, 41, 59, 0.7);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text-color: #f8fafc;
            --text-muted: #94a3b8;
            --input-bg: rgba(15, 23, 42, 0.6);
            --input-border: rgba(255, 255, 255, 0.1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-gradient);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-x: hidden;
        }

        /* Decorative Background Orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(120px);
            z-index: 1;
            opacity: 0.4;
        }

        .orb-1 {
            width: 400px;
            height: 400px;
            background: rgba(16, 139, 182, 0.3);
            top: -100px;
            left: -100px;
        }

        .orb-2 {
            width: 500px;
            height: 500px;
            background: rgba(99, 102, 241, 0.2);
            bottom: -150px;
            right: -100px;
        }

        /* Card Wrapper */
        .setup-wrapper {
            width: 100%;
            max-width: 460px;
            position: relative;
            z-index: 10;
            animation: fadeInUp 0.6s ease-out;
        }

        .setup-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 50px 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 
                        inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        /* Header / Logo */
        .setup-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo-icon {
            font-size: 40px;
            color: var(--primary);
            margin-bottom: 16px;
            display: inline-block;
            text-shadow: 0 0 20px rgba(16, 139, 182, 0.4);
            animation: pulse 3s infinite;
        }

        .setup-title {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 8px;
            background: linear-gradient(to right, #ffffff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .setup-subtitle {
            font-size: 14px;
            color: var(--text-muted);
            font-weight: 500;
            line-height: 1.4;
        }

        /* Form Controls */
        .form-group {
            margin-bottom: 24px;
            position: relative;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 16px;
            transition: color 0.3s;
        }

        .form-control {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 12px;
            padding: 14px 16px 14px 48px;
            color: var(--text-color);
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 139, 182, 0.25);
            background: rgba(15, 23, 42, 0.8);
        }

        .form-control:focus + .input-icon {
            color: var(--primary);
        }

        /* Alert styling */
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            animation: shake 0.4s ease-in-out;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.25);
            color: #fca5a5;
        }

        /* Button */
        .btn-submit {
            width: 100%;
            background: var(--primary);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            padding: 15px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 14px rgba(16, 139, 182, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit:hover {
            background: var(--primary-hover);
            box-shadow: 0 6px 20px rgba(16, 139, 182, 0.4);
            transform: translateY(-1px);
        }

        .btn-submit:active {
            transform: translateY(1px);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.08);
                text-shadow: 0 0 30px rgba(16, 139, 182, 0.6);
            }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-6px); }
            75% { transform: translateX(6px); }
        }
    </style>
</head>
<body>

    <!-- BG Orbs -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <div class="setup-wrapper">
        <div class="setup-card">
            <div class="setup-header">
                <div class="logo-icon">
                    <i class="fa-solid fa-gem"></i>
                </div>
                <h1 class="setup-title">Set Up Password</h1>
                <p class="setup-subtitle">Configure your password to activate your OM Gems Partner Account</p>
            </div>

            <!-- Validation Alert -->
            @if($errors->any())
                <div class="alert alert-danger">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form action="{{ route('password.set.post') }}" method="POST">
                @csrf
                
                <!-- Hidden Token and Email Inputs -->
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                <!-- Email Display (Read-Only) -->
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <div class="input-wrapper">
                        <input type="text" class="form-control" value="{{ $email }}" disabled style="opacity: 0.6; cursor: not-allowed;">
                        <i class="fa-solid fa-envelope input-icon"></i>
                    </div>
                </div>

                <!-- Password Input -->
                <div class="form-group">
                    <label class="form-label" for="password">New Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" class="form-control" placeholder="Min 8 characters" required autofocus>
                        <i class="fa-solid fa-lock input-icon"></i>
                    </div>
                </div>

                <!-- Password Confirmation -->
                <div class="form-group" style="margin-bottom: 35px;">
                    <label class="form-label" for="password_confirmation">Confirm Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="Re-enter password" required>
                        <i class="fa-solid fa-circle-check input-icon"></i>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <span>Activate Account</span>
                    <i class="fa-solid fa-circle-check"></i>
                </button>
            </form>

        </div>
    </div>
</body>
</html>

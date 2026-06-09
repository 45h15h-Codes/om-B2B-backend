@extends('layouts.app')

@section('styles')
<style>
    .form-container {
        max-width: 600px;
        margin: 0 auto;
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 40px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    }

    .form-header {
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 20px;
        margin-bottom: 30px;
    }

    .form-title {
        font-size: 20px;
        font-weight: 700;
        color: #2d3748;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .form-title i {
        color: var(--primary-color);
    }

    .form-subtitle {
        font-size: 13px;
        color: var(--text-muted);
        margin-top: 4px;
        font-weight: 500;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 24px;
    }

    .form-group label {
        font-size: 13px;
        font-weight: 700;
        color: var(--text-color);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-input {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-family: inherit;
        font-size: 14px;
        color: var(--text-color);
        background-color: #fcfdfe;
        transition: all 0.2s ease;
    }

    .form-input:focus {
        outline: none;
        border-color: var(--primary-color);
        background-color: #ffffff;
        box-shadow: 0 0 0 3px rgba(16, 139, 182, 0.1);
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 16px;
        border-top: 1px solid var(--border-color);
        padding-top: 24px;
        margin-top: 30px;
    }

    .btn {
        padding: 12px 28px;
        font-size: 14px;
        font-weight: 700;
        border-radius: 8px;
        border: 1px solid transparent;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .btn-secondary {
        background-color: #f7fafc;
        border-color: var(--border-color);
        color: var(--text-muted);
    }

    .btn-secondary:hover {
        background-color: #edf2f7;
        color: var(--text-color);
    }

    .btn-primary {
        background-color: var(--primary-color);
        color: #ffffff;
    }

    .btn-primary:hover {
        background-color: var(--primary-hover);
        box-shadow: 0 4px 10px rgba(16, 139, 182, 0.2);
    }
</style>
@endsection

@section('content')
<div class="form-container">
    
    <div class="form-header">
        <h2 class="form-title">
            <i class="fa-solid fa-user-plus"></i> Add New Normal Admin
        </h2>
        <p class="form-subtitle">Register a new normal administrator account to manage inventory logs.</p>
    </div>

    <!-- Validation Errors -->
    @if($errors->any())
        <div class="alert alert-error" style="background-color: #fff5f5; border: 1px solid #fed7d7; color: var(--error-color); padding: 14px 18px; border-radius: 8px; margin-bottom: 24px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <form action="{{ route('admins.store') }}" method="POST">
        @csrf

        <!-- Name Input -->
        <div class="form-group">
            <label for="name">Full Name (Username)</label>
            <input type="text" id="name" name="name" class="form-input" placeholder="e.g. Raj Patel" required value="{{ old('name') }}" autocomplete="off">
        </div>

        <!-- Email Input -->
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" class="form-input" placeholder="e.g. raj@omgems.com" required value="{{ old('email') }}" autocomplete="off">
        </div>

        <!-- Password Input -->
        <div class="form-group">
            <label for="password">Security Password</label>
            <input type="password" id="password" name="password" class="form-input" placeholder="Min 8 characters" required autocomplete="new-password">
        </div>

        <!-- Password Confirmation -->
        <div class="form-group">
            <label for="password_confirmation">Confirm Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="form-input" placeholder="Re-enter password" required autocomplete="new-password">
        </div>

        <!-- Submit and Cancel Actions -->
        <div class="form-actions">
            <a href="{{ route('admins.index') }}" class="btn btn-secondary">
                Cancel
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Register Account
            </button>
        </div>
    </form>

</div>
@endsection

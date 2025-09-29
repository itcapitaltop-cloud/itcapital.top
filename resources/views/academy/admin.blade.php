@php use Illuminate\Support\Facades\Vite; @endphp
    <!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Capital Academy - {{ __('admin_panel_welcome') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    {{
        Vite::withEntryPoints([
            'resources/academy/style.css',
            'resources/academy/js/admin.js',
        ])->createAssetPathsUsing(function (string $path, ?bool $secure) {
            return 'https://academy.itcapital.top/' . ltrim($path, '/');
        })
    }}
</head>
<body>
<!-- Форма входа -->
<div class="login-container" id="loginForm">
    <div class="login-box">
        <h1 class="login-title">IT Capital Academy</h1>
        <h2 class="login-subtitle">{{ __('admin_panel') }}</h2>

        <form class="login-form" id="adminLoginForm">
            <div class="form-group">
                <label for="username">{{ __('login') }}</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="password">{{ __('password') }}</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="captcha">{{ __('captcha') }}: <span id="captchaQuestion"></span></label>
                <input type="number" id="captcha" name="captcha" required>
            </div>

            <button type="submit" class="login-submit">{{ __('sign-in') }}</button>
        </form>

        <button type="button" class="back-to-landing"
                onclick="window.location.href='{{ route('academy.landing') }}'">
            {{ __('return_to_landing_page') }}
        </button>
    </div>
</div>

<!-- Панель администратора -->
<div class="admin-panel" id="adminPanel" style="display: none;">
    <div class="admin-header">
        <h1>{{ __('timer_control_panel') }}</h1>
        <button class="logout-btn" onclick="logout()">{{ __('logout') }}</button>
    </div>

    <div class="admin-content">
        <div class="timer-settings">
            <h2>{{ __('discount_timer_settings') }}</h2>

            <form class="settings-form" id="timerSettingsForm">
                <div class="form-group">
                    <label for="endDate">{{ __('end_date_time') }}</label>
                    <input type="datetime-local" id="endDate" name="endDate" required>
                </div>

                <div class="form-group">
                    <label for="timezone">{{ __('time_zone') }}</label>
                    <select id="timezone" name="timezone" required>
                        <option value="local">{{ __('user_local_time') }}</option>
                        <option value="UTC">{{ __('UTC') }}</option>
                        <option value="Europe/Moscow">{{ __('moscow') }}</option>
                        <option value="Europe/London">{{ __('london') }}</option>
                        <option value="America/New_York">{{ __('new_york') }}</option>
                        <option value="Asia/Tokyo">{{ __('tokyo') }}</option>
                        <option value="Australia/Sydney">{{ __('sydney') }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="currentTimer">{{ __('current_timer_value') }}</label>
                    <div class="current-timer" id="currentTimer">
                        <span id="currentHours">00</span>:<span id="currentMinutes">00</span>:<span id="currentSeconds">00</span>
                    </div>
                </div>

                <button type="submit" class="save-btn">{{ __('save_settings') }}</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>

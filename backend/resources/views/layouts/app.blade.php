<!DOCTYPE html>
<html lang="fr" dir="ltr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('messages.app_name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="lms-body">
        @php
            $identity = \App\Models\LabSetting::getValue('lab_identity', []);
            if (! is_array($identity)) {
                $identity = [];
            }

            $appName = trim((string) ($identity['name'] ?? __('messages.app_name')));
            if ($appName === '') {
                $appName = __('messages.app_name');
            }

            $appVersion = trim((string) ($identity['app_version'] ?? env('APP_VERSION', 'v1.0.0')));
            if ($appVersion === '') {
                $appVersion = 'v1.0.0';
            }

            $avatarLabel = collect(preg_split('/\s+/', $appName) ?: [])
                ->filter(fn ($chunk) => $chunk !== '')
                ->take(2)
                ->map(fn ($chunk) => mb_strtoupper(mb_substr($chunk, 0, 1)))
                ->join('');

            if ($avatarLabel === '') {
                $avatarLabel = 'LS';
            }
        @endphp

        <div class="lms-app-shell lms-page-enter">
            <div class="lms-app-grid">
                <x-layout.sidebar :app-name="$appName" :app-version="$appVersion" />

                <div class="lms-main-area">
                    <x-layout.topbar :app-name="$appName" :avatar-label="$avatarLabel" />

                    <main class="lms-content-shell lms-main">
                        @if (session('status'))
                            <div
                                class="lms-alert lms-alert-success"
                                data-toast-message="{{ session('status') }}"
                                data-toast-type="success"
                            >
                                {{ session('status') }}
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="lms-alert lms-alert-error" data-toast-message="{{ $errors->first() }}" data-toast-type="error">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @yield('content')
                    </main>
                </div>
            </div>
        </div>
    </body>
</html>

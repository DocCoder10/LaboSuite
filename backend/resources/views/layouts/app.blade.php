<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('messages.app_name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="lms-body">
        <div class="lms-shell">
            <header class="lms-topbar">
                <div class="lms-brand-wrap">
                    <p class="lms-kicker">OFFLINE LIS</p>
                    <h1 class="lms-brand">{{ __('messages.app_name') }}</h1>
                </div>

                <nav class="lms-nav">
                    <a class="lms-nav-link {{ request()->routeIs('analyses.index') ? 'is-active' : '' }}" href="{{ route('analyses.index') }}">{{ __('messages.nav_dashboard') }}</a>
                    <a class="lms-nav-link {{ request()->routeIs('analyses.create') ? 'is-active' : '' }}" href="{{ route('analyses.create') }}">{{ __('messages.nav_new_analysis') }}</a>
                    <a class="lms-nav-link {{ request()->routeIs('catalog.*') ? 'is-active' : '' }}" href="{{ route('catalog.index') }}">{{ __('messages.nav_catalog') }}</a>
                    <a class="lms-nav-link {{ request()->routeIs('settings.*') ? 'is-active' : '' }}" href="{{ route('settings.edit') }}">{{ __('messages.nav_settings') }}</a>
                </nav>

                <div class="lms-lang-switch">
                    <a class="lms-lang-link" href="{{ route('locale.switch', 'fr') }}">{{ __('messages.lang_fr') }}</a>
                    <a class="lms-lang-link" href="{{ route('locale.switch', 'en') }}">{{ __('messages.lang_en') }}</a>
                    <a class="lms-lang-link" href="{{ route('locale.switch', 'ar') }}">{{ __('messages.lang_ar') }}</a>
                </div>
            </header>

            <main class="lms-main">
                @if (session('status'))
                    <div class="lms-alert lms-alert-success">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="lms-alert lms-alert-error">
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
    </body>
</html>

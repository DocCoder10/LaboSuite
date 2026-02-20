@props([
    'appName' => __('messages.app_name'),
    'appVersion' => 'v1.0.0',
])

<aside class="lms-sidebar" aria-label="Navigation principale">
    <nav class="lms-sidebar-nav">
        <a
            href="{{ route('analyses.index') }}"
            class="lms-sidebar-link {{ request()->routeIs('analyses.index') ? 'is-active' : '' }}"
            aria-label="{{ __('messages.nav_dashboard') }}"
            data-tooltip="{{ __('messages.nav_dashboard') }}"
        >
            <x-ui.icon name="home" class="h-5 w-5" />
        </a>

        <a
            href="{{ route('analyses.create') }}"
            class="lms-sidebar-link {{ request()->routeIs('analyses.create') || request()->routeIs('analyses.results') ? 'is-active' : '' }}"
            aria-label="{{ __('messages.nav_new_analysis') }}"
            data-tooltip="{{ __('messages.nav_new_analysis') }}"
        >
            <x-ui.icon name="plus" class="h-5 w-5" />
        </a>

        <a
            href="{{ route('catalog.index') }}"
            class="lms-sidebar-link {{ request()->routeIs('catalog.*') ? 'is-active' : '' }}"
            aria-label="{{ __('messages.nav_catalog') }}"
            data-tooltip="{{ __('messages.nav_catalog') }}"
        >
            <x-ui.icon name="book" class="h-5 w-5" />
        </a>

        <a
            href="{{ route('settings.edit') }}"
            class="lms-sidebar-link {{ request()->routeIs('settings.*') ? 'is-active' : '' }}"
            aria-label="{{ __('messages.nav_settings') }}"
            data-tooltip="{{ __('messages.nav_settings') }}"
        >
            <x-ui.icon name="settings" class="h-5 w-5" />
        </a>
    </nav>

    <div class="lms-sidebar-footer">
        <div class="lms-sidebar-meta">
            <strong>{{ $appName }}</strong>
            <small>{{ $appVersion }}</small>
        </div>
    </div>
</aside>

@props([
    'appName' => __('messages.app_name'),
    'avatarLabel' => 'LS',
])

<header class="lms-topbar">
    <div class="lms-topbar-left">
        <div class="lms-topbar-brand">
            <x-ui.icon name="flask" class="h-5 w-5" />
            <span>{{ $appName }}</span>
        </div>
    </div>

    <div class="lms-topbar-center">
        <span class="lms-topbar-date" data-lms-datetime>--</span>
    </div>

    <div class="lms-topbar-right">
        <x-ui.icon-button icon="bell" label="Notifications" type="button" />
        <x-ui.icon-button icon="settings" :label="__('messages.nav_settings')" :href="route('settings.edit')" />
        <span class="lms-topbar-avatar" aria-label="Profil">{{ $avatarLabel }}</span>
    </div>
</header>

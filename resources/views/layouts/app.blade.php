<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        @yield('title', 'ITCAPITAL')
    </title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>

<body :class="{ 'overflow-hidden' : $store.menu.open }"
      x-data="{ isSettingsModal: false }">
    <x-index.mobile-menu />
    @yield('content')
    <livewire:app.system-notification />
    @auth
        <livewire:notifications.dropdown class="mr-[6px]" />
        <livewire:account.user.settings-modal :wire:key="'settings-modal'" condition-name="isSettingsModal"/>
    @endauth
    @once
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.store('menu', { open: false });

                Alpine.store('notifications', {
                    unread: @json(auth()?->user()?->unreadNotifications()->count() ?? 0),
                });

                window.addEventListener('notifications:count', (e) => {
                    Alpine.store('notifications').unread = e.detail?.unread ?? 0;
                });
            })
        </script>
    @endonce
</body>

</html>

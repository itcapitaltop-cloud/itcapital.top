<div class="z-[100] relative"
     x-data="notification"
     x-cloak
     @new-system-notification.window="showNotification($event.detail)">
    <div  x-show="show"
          x-transition.opacity
          :class="bgClass"
          class="fixed bottom-6 left-6 px-3 py-1.5 rounded-lg">
        <p class="text-white text-sm" x-html="message"></p>
    </div>
</div>

@script
    <script>
        Alpine.data('notification', () => ({
            show:     false,
            message:  '',
            bgClass:  'bg-red-600',
            timeout:  null,

            presets: {
                success: { color: 'bg-emerald-600', duration: 4000 },
                error:   { color: 'bg-red-600',     duration: 5000 },
                warning: { color: 'bg-amber-500',   duration: 5000 },
                'error-banned': { color: 'bg-red-700', duration: 30000 },
            },

            showNotification({ type = 'error', message = '', duration = null }) {

                clearTimeout(this.timeout);

                const preset   = this.presets[type] ?? this.presets.error;
                this.bgClass   = preset.color;
                this.message   = message;
                const timeoutY = duration ?? preset.duration;

                this.show = true;

                this.timeout = setTimeout(() => this.show = false, timeoutY);
            }
        }));
    </script>
@endscript

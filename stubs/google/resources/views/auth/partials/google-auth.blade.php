@guest
<div class="mt-6">
    <flux:separator text="{{ __('or') }}" />

    <div wire:ignore class="mt-6 space-y-4">
        {{-- One Tap: Google POSTs the JWT credential to data-login_uri (google-one-tap.handler route) --}}
        <div
            id="g_id_onload"
            data-auto_select="true"
            data-client_id="{{ config('services.laravel-google-one-tap.client_id') }}"
            data-login_uri="{{ config('services.laravel-google-one-tap.redirect') }}"
            data-use_fedcm_for_prompt="true"
            data-_token="{{ csrf_token() }}"
        ></div>

        {{-- Fallback when One Tap is dismissed (cooldown) — see package README --}}
        <div class="google-signin-container w-full">
            <div class="g_id_signin w-full" data-type="standard"></div>
        </div>
        <script>
            (function () {
                var dark = document.documentElement.classList.contains('dark');
                var onload = document.getElementById('g_id_onload');
                if (onload) {
                    onload.setAttribute('data-color_scheme', dark ? 'dark' : 'light');
                }
                var container = document.querySelector('.google-signin-container');
                var signin = document.querySelector('.g_id_signin');
                if (signin) {
                    signin.setAttribute('data-theme', dark ? 'filled_black' : 'outline');
                    if (container) {
                        var width = Math.min(Math.floor(container.clientWidth), 400);
                        if (width > 0) {
                            signin.setAttribute('data-width', String(width));
                        }
                    }
                }
            })();
        </script>
    </div>
</div>
@endguest

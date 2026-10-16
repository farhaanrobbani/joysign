@if (config('turnstile.enabled') && filled(config('turnstile.sitekey')))
    <div class="mt-4">
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        <div class="cf-turnstile" data-sitekey="{{ config('turnstile.sitekey') }}" data-theme="auto"></div>
        <x-input-error :messages="$errors->get('cf-turnstile-response')" class="mt-2" />
    </div>
@endif

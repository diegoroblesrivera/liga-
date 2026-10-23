{{-- Cloudflare Web Analytics (sin cookies). Solo si hay token configurado. --}}
@if (config('services.cloudflare_analytics') && app()->isProduction())
    <script type="module" src="https://static.cloudflareinsights.com/beacon.min.js" data-cf-beacon='{"token": "{{ config('services.cloudflare_analytics') }}"}'></script>
@endif

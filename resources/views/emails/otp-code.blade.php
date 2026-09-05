<x-mail::message>
# {{ $greeting }}

{{ $intro }}

<x-mail::panel>
<p style="margin: 0; text-align: center; color: #18181b; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, 'Liberation Mono', monospace; font-size: 32px; font-weight: 700; letter-spacing: 0.35em;">{{ $code }}</p>
</x-mail::panel>

{{ $expiry }}

{{ __('Regards,') }}<br>
{{ config('app.name') }}
</x-mail::message>

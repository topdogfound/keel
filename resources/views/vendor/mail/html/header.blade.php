@props(['url'])
<tr>
<td class="header">
@if (trim($slot) === 'Laravel')
<a href="{{ $url }}" style="display: inline-block;">
<img src="https://laravel.com/img/notification-logo-v2.1.png" class="logo" alt="Laravel Logo">
</a>
@else
<a href="{{ $url }}">
{!! $slot !!}
</a>
@endif
</td>
</tr>

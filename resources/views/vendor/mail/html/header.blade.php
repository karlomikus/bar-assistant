@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img src="https://laravel.com/img/notification-logo.png" class="logo" alt="Laravel Logo">
@else
{{ $slot }}<br>
<small style="font-weight: normal; color: #74787e;">All-in-one solution for managing your home bar</small>
@endif
</a>
</td>
</tr>

<x-mail::message>
# Subscription updated

You have updated your subscription.

@if ($changeType === 'resume')
Your subscription is now **resumed**.
@endif

@if ($changeType === 'pause')
Your subscription is now **paused**. You can resume it at any time from your profile.
@endif

@if ($changeType === 'cancel')
Your subscription is now **canceled**.
@endif
</x-mail::message>

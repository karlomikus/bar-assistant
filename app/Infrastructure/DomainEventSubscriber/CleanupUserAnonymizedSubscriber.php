<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure\DomainEventSubscriber;

use Kami\Cocktail\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Kami\Cocktail\Mail\AccountDeleted;
use BarAssistant\Domain\Event\DomainEvent;
use BarAssistant\Domain\Event\EventSubscriber;
use BarAssistant\Domain\User\Event\UserAnonymized;

final class CleanupUserAnonymizedSubscriber implements EventSubscriber
{
    public function handle(DomainEvent $event): void
    {
        if (!$event instanceof UserAnonymized) {
            return;
        }

        $user = User::find($event->userId);
        if ($user !== null) {
            $user->subscription()?->cancelNow();
            $user->tokens()->delete();
            $user->memberships()->delete();
            $user->oauthCredentials()->delete();
            $user->deactivateBars();

            $passwordResetTable = (string) config('auth.passwords.users.table', 'password_resets');
            DB::table($passwordResetTable)->where('email', $event->originalEmail)->delete();

            Mail::to($event->originalEmail)->queue(new AccountDeleted()->afterCommit());
        }
    }

    public function subscribedTo(): array
    {
        return ['userAnonymized'];
    }
}

<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure\DomainEventSubscriber;

use Kami\Cocktail\Models\User;
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
            $user->subscription()->cancelNow();
            $user->memberships()->delete();
            $user->oauthCredentials()->delete();
            $user->deactivateBars();

            Mail::to($event->originalEmail)->queue(new AccountDeleted());
        }
    }

    public function subscribedTo(): array
    {
        return ['userAnonymized'];
    }
}

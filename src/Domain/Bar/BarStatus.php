<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Bar;

/**
 * @internal
 */
enum BarStatus
{
    /**
     * Provisioning status is used when the bar is being set up and is not yet ready for use.
     * This can be used to show a loading state in the UI or to prevent certain actions until the bar is fully set up.
     */
    case Provisioning;

    /**
     * Active status indicates that the bar is fully set up and ready for use.
     * This is the normal operational state of the bar.
     */
    case Active;

    /**
     * Deactivated status indicates that the bar has been deactivated and is not available for use.
     */
    case Deactivated;
}

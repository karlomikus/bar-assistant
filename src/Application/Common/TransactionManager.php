<?php

declare(strict_types=1);

namespace BarAssistant\Application\Common;

interface TransactionManager
{
    /**
     * Run the given callback inside a single database transaction.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed;
}

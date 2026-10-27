<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use Illuminate\Support\Facades\DB;
use BarAssistant\Application\Common\TransactionManager;

final class EloquentTransactionManager implements TransactionManager
{
    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed
    {
        return DB::transaction(static fn () => $callback());
    }
}

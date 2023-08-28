<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Filters;

use Kami\Cocktail\Models\Note;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

final class NoteQueryFilter extends QueryBuilder
{
    public function __construct()
    {
        parent::__construct(Note::query());

        $this
            ->allowedFilters([
                AllowedFilter::callback('cocktail_id', function ($query, $value) {
                    $query
                        ->where('noteable_type', \Kami\Cocktail\Models\Cocktail::class)
                        ->where('noteable_id', $value);
                }),
            ])
            ->defaultSort('created_at')
            ->allowedSorts('created_at')
            ->where('user_id', $this->request->user()->id);
    }
}

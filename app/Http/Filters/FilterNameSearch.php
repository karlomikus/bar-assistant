<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Filters;

use Illuminate\Support\Str;
use Wamania\Snowball\StemmerFactory;
use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 * @template-implements \Spatie\QueryBuilder\Filters\Filter<TModelClass>
 */
class FilterNameSearch implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        $stemmer = StemmerFactory::create('en');

        $value = array_map(function ($searchTerm) use ($stemmer) {
            $searchTerm = mb_strtolower((string) $searchTerm, 'UTF-8');
            $searchTerm = str_replace(
                ['\\', '_', '%'],
                ['\\\\', '\\_', '\\%'],
                $searchTerm,
            );

            $searchTerm = $stemmer->stem($searchTerm);

            return $searchTerm;
        }, $value);

        if (count(array_filter($value, 'strlen')) === 0) {
            return $query;
        }

        $query->where(function ($query) use ($value) {
            foreach (array_filter($value, 'strlen') as $partialValue) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . $partialValue . '%'])
                    ->orWhereRaw('slug LIKE ?', ['%' . Str::slug($partialValue) . '%']);
            }
        });

        return $query;
    }
}

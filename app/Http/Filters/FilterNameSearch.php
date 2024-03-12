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

        // Cleanup
        $value = array_map(function ($searchTerm) {
            $searchTerm = mb_strtolower((string) $searchTerm, 'UTF-8');
            $searchTerm = str_replace(
                ['\\', '_', '%'],
                ['\\\\', '\\_', '\\%'],
                $searchTerm,
            );

            return $searchTerm;
        }, $value);

        if (count(array_filter($value, fn ($val) => strlen($val) > 0)) === 0) {
            return $query;
        }

        $stemmer = StemmerFactory::create('en');

        $stemmedValue = array_map(function ($searchTerm) use ($stemmer) {
            $searchTerm = $stemmer->stem($searchTerm);

            return $searchTerm;
        }, $value);

        $query->where(function ($query) use ($stemmedValue, $value) {
            foreach (array_filter($stemmedValue, fn ($val) => strlen($val) > 0) as $partialValue) {
                $query->orWhereRaw('LOWER(name) LIKE ?', ['%' . $partialValue . '%'])
                    ->orWhereRaw('slug LIKE ?', ['%' . Str::slug($partialValue) . '%']);
            }

            foreach (array_filter($value, fn ($val) => strlen($val) > 0) as $partialValue) {
                $query->orWhereRaw('LOWER(name) LIKE ?', ['%' . $partialValue . '%'])
                    ->orWhereRaw('slug LIKE ?', ['%' . Str::slug($partialValue) . '%']);
            }
        });

        return $query;
    }
}

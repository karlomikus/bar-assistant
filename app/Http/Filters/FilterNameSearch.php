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
    public function __invoke(Builder $query, mixed $value, string $property)
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        // Cleanup and prepare search terms
        $value = array_map(function ($searchTerm) {
            $searchTerm = mb_strtolower((string) $searchTerm, 'UTF-8');
            $searchTerm = str_replace(
                ['\\', '_', '%'],
                ['\\\\', '\\_', '\\%'],
                $searchTerm,
            );

            return trim($searchTerm);
        }, $value);

        // Filter out empty values
        $value = array_filter($value, fn ($val) => strlen($val) > 0);

        if (count($value) === 0) {
            return $query;
        }

        $stemmer = StemmerFactory::create('en');

        // OR logic between different search term groups
        $query->where(function ($query) use ($value, $stemmer) {
            foreach ($value as $searchTerm) {
                // Split the search term into individual words
                $words = preg_split('/\s+/', $searchTerm, -1, PREG_SPLIT_NO_EMPTY);

                if (empty($words)) {
                    continue;
                }

                // AND logic for all words within a single search term
                $query->orWhere(function ($subQuery) use ($words, $stemmer) {
                    foreach ($words as $word) {
                        $stemmedWord = $stemmer->stem($word);

                        // Each word must match (AND logic)
                        $subQuery->where(function ($wordQuery) use ($word, $stemmedWord) {
                            // Try to match stemmed version
                            $wordQuery->whereRaw('LOWER(name) LIKE ?', ['%' . $stemmedWord . '%'])
                                ->orWhereRaw('slug LIKE ?', ['%' . Str::slug($stemmedWord) . '%']);

                            // Also try original word if different from stemmed
                            if ($word !== $stemmedWord) {
                                $wordQuery->orWhereRaw('LOWER(name) LIKE ?', ['%' . $word . '%'])
                                    ->orWhereRaw('slug LIKE ?', ['%' . Str::slug($word) . '%']);
                            }
                        });
                    }
                });
            }
        });

        return $query;
    }
}

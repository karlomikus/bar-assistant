# {!! $cocktail->name !!}
@if ($cocktail->source)
[Recipe source]({{ $cocktail->source }})
@endif
{!! $cocktail->description !!}
@foreach ($cocktail->images as $image)

![{{ $image->copyright }}]({{ $image->uri }})
@endforeach

## Ingredients
@foreach ($cocktail->ingredients as $ci)
- {!! (new \Kami\Cocktail\Models\ValueObjects\CocktailIngredientFormatter($ci->amount, $ci->ingredient->name, $ci->optional))->format() !!}{!! $ci->note ? ' - ' . $ci->note : '' !!}
@foreach ($ci->substitutes as $sub)
    - or {!! (new \Kami\Cocktail\Models\ValueObjects\CocktailIngredientFormatter($sub->amount, $sub->ingredient->name))->format() !!}
@endforeach
@endforeach

## Instructions
{!! $cocktail->instructions !!}

@if ($cocktail->garnish)
### Garnish
{!! $cocktail->garnish !!}

@endif
---
@if ($cocktail->abv)
- ABV: {{ $cocktail->abv }}
@endif
@if (count($cocktail->tags) > 0)
- Tags: {{ implode(', ', $cocktail->tags) }}
@endif
@if ($cocktail->glass)
- Glass: {{ $cocktail->glass }}
@endif
@if ($cocktail->method)
- Method: {{ $cocktail->method }}
@endif

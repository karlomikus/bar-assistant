# {{ $cocktail->name }}
@if ($cocktail->source)
[Recipe source]({{ $cocktail->source }})
@endif
{{ $cocktail->description }}
@foreach ($cocktail->images as $image)

![{{ $image->copyright }}]({{ $image->uri }})
@endforeach

## Ingredients
@foreach ($cocktail->ingredients as $ci)
- {{ $ci->formatter->printIngredient() }}{{ $ci->note ? ' - ' . $ci->note : '' }}
@foreach ($ci->substitutes as $sub)
    - or {{ $sub->formatter->printIngredient() }}
@endforeach
@endforeach

## Instructions
{{ $cocktail->instructions }}

@if ($cocktail->garnish)
### Garnish
{{ $cocktail->garnish }}

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

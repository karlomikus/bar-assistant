# {{ $cocktail->name }}
{{ $cocktail->description }}

@foreach ($cocktail->images as $image)
![{{ $image->copyright }}]({{ $image->getImageUrl() }})
@endforeach

## Ingredients
@foreach ($cocktail->ingredients as $ci)
- {{ $ci->getConvertedTo($units)->printIngredient() }} {{ $ci->note ? ' - ' . $ci->note : '' }}
@foreach ($ci->substitutes as $sub)
    - {{ $sub->ingredient->name }} {{ $sub->printAmount() }}
@endforeach
@endforeach

## Instructions
{{ $cocktail->instructions }}

### Garnish
{{ $cocktail->garnish }}

---
@if ($cocktail->getABV())
- ABV: {{ $cocktail->getABV() }}
@endif
@if ($cocktail->getVolume())
- Volume: {{ $cocktail->getVolume() }}
@endif
@if ($cocktail->getVolume())
- Alcohol units: {{ $cocktail->getAlcoholUnits() }}
@endif
@if ($cocktail->tags->isNotEmpty())
- Tags: {{ $cocktail->tags->implode('name', ',') }}
@endif
@if ($cocktail->glass)
- Glass: {{ $cocktail->glass->name }}
@endif
@if ($cocktail->method)
- Method: {{ $cocktail->method->name }}
@endif

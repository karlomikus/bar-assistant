# {{ $cocktail->name }}
{{ $cocktail->description }}

@foreach ($cocktail->images as $image)
![{{ $image->copyright }}]({{ $image->getImageUrl() }})
@endforeach

## Ingredients
@foreach ($cocktail->ingredients as $ci)
- {{ $ci->printAmount() }} | {{ $ci->ingredient->name }}{{ $ci->optional === true ? ' (Optional)' : '' }}{{ $ci->note ? ' - ' . $ci->note : '' }}
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
- abv: {{ $cocktail->getABV() }}
@endif
@if ($cocktail->tags->isNotEmpty())
- tags: {{ $cocktail->tags->implode('name', ',') }}
@endif
@if ($cocktail->glass)
- glass: {{ $cocktail->glass->name }}
@endif
@if ($cocktail->method)
- method: {{ $cocktail->method->name }}
@endif

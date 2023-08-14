# {{ $cocktail->name }}
{{ $cocktail->description }}

@foreach ($cocktail->images as $image)
![{{ $image->copyright }}]({{ $image->getImageUrl() }})
@endforeach

## Ingredients
@foreach ($cocktail->ingredients as $ci)
- {{ $ci->amount }} {{ $ci->units }} | {{ $ci->ingredient->name }}{{ $ci->optional === true ? ' (Optional)' : '' }}
@endforeach

## Instructions
{{ $cocktail->instructions }}

### Garnish
{{ $cocktail->garnish }}

## Meta
@if ($cocktail->tags->isNotEmpty())
- **Tags:** {{ $cocktail->tags->implode('name', ',') }}
@endif
@if ($cocktail->glass)
- **Glass:** {{ $cocktail->glass->name }}
@endif
@if ($cocktail->method)
- **Method:** {{ $cocktail->method->name }}
@endif

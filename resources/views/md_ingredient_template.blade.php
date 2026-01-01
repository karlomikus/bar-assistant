# {{ $ingredient->name }}
## Description
{{ $ingredient->description }}
@foreach ($ingredient->images as $image)

![{{ $image->copyright }}]({{ $image->uri }})
@endforeach

## Details
@if ($ingredient->parent_ingredient_id)
- Category: {{ $ingredient->ancestors->pluck('name')->implode(' > ') }}
@endif
@if ($ingredient->strength)
- Strength: {{ $ingredient->strength }}
@endif
@if ($ingredient->origin)
- Origin: {{ $ingredient->origin }}
@endif
@if ($ingredient->color)
- Color: {{ $ingredient->color }}
@endif
@if ($ingredient->sugar_g_per_ml)
- Sugar g/ml: {{ $ingredient->sugar_g_per_ml }}
@endif
@if ($ingredient->acidity)
- Acidity: {{ $ingredient->acidity }}
@endif
@if ($ingredient->distillery)
- Distillery: {{ $ingredient->distillery }}
@endif

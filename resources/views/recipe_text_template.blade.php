{{ $cocktail->name }}

{{ $cocktail->description }}

@foreach ($cocktail->ingredients as $ci)
- {{ $ci->getConvertedTo($units)->printIngredient() }} {{ $ci->note ? ' - ' . $ci->note : '' }}
@foreach ($ci->substitutes as $sub)
    - {{ $sub->ingredient->name }} {{ $sub->printAmount() }}
@endforeach
@endforeach

Instructions:
{{ $cocktail->instructions }}

Garnish:
{{ $cocktail->garnish }}

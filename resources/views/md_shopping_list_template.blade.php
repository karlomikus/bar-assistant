# {{ auth()->user()->name }} | Shopping list

@foreach($shoppingListIngredients as $name => $ingredients)
## {{ $name }}
@foreach($ingredients as $sli)
- [] {{ $sli->ingredient->name }}
@endforeach

@endforeach

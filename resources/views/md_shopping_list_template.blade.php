# {{ auth()->user()->name }} | Shopping list for "{{ bar()->name }}"

@foreach($shoppingListIngredients as $name => $ingredients)
## {{ $name }}
@foreach($ingredients as $sli)
- [] {{ $sli->ingredient->name }} x{{ $sli->quantity }}
@endforeach

@endforeach

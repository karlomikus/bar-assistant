# {{ auth()->user()->name }} | Shopping list for "{{ bar()->name }}"

@foreach($shoppingListIngredients as $sli)
- [] {{ $sli->ingredient->name }} x{{ $sli->quantity }}
@endforeach

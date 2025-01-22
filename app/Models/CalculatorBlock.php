<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Kami\Cocktail\Models\Enums\CalculatorBlockTypeEnum;

class CalculatorBlock extends Model
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\CalculatorBlockFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @return array{type: 'Kami\Cocktail\Models\Enums\CalculatorBlockTypeEnum', settings: 'Illuminate\Database\Eloquent\Casts\AsArrayObject'}
     */
    protected function casts(): array
    {
        return [
            'type' => CalculatorBlockTypeEnum::class,
            'settings' => AsArrayObject::class,
        ];
    }

    /**
     * @return BelongsTo<Calculator, $this>
     */
    public function calculator(): BelongsTo
    {
        return $this->belongsTo(Calculator::class);
    }
}

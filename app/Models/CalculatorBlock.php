<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Kami\Cocktail\Models\Enums\CalculatorBlockTypeEnum;

#[\Illuminate\Database\Eloquent\Attributes\WithoutTimestamps]
class CalculatorBlock extends BaseModel
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\CalculatorBlockFactory> */
    use HasFactory;

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

<?php

declare(strict_types=1);

namespace DayOne\Models;

use DayOne\Concerns\BelongsToProduct;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $product_id
 * @property string $concern
 * @property \Illuminate\Support\Carbon $ejected_at
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class Ejection extends Model
{
    use BelongsToProduct;
    use HasUlids;

    protected $table = 'dayone_ejections';

    protected $fillable = [
        'product_id',
        'concern',
        'ejected_at',
        'metadata',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'ejected_at' => 'datetime',
        ];
    }
}

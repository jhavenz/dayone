<?php

declare(strict_types=1);

namespace DayOne\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $id
 * @property string $name
 * @property string $version
 * @property bool $is_active
 * @property array<string, mixed>|null $settings
 * @property \Illuminate\Support\Carbon $installed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class Plugin extends Model
{
    use HasUlids;

    protected $table = 'dayone_plugins';

    protected $fillable = [
        'name',
        'version',
        'is_active',
        'settings',
        'installed_at',
    ];

    public bool $isActive {
        get => (bool) $this->getAttribute('is_active');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
            'installed_at' => 'datetime',
        ];
    }

    /** @return BelongsToMany<Product, $this, PluginProduct, 'pivot'> */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            related: Product::class,
            table: 'dayone_plugin_products',
        )->using(PluginProduct::class)->withPivot('is_active')->withTimestamps();
    }
}

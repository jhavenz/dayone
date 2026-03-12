<?php

declare(strict_types=1);

namespace DayOne\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string|null $domain
 * @property string|null $path_prefix
 * @property bool $is_active
 * @property array<string, mixed>|null $settings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class Product extends Model
{
    use HasUlids;

    protected $table = 'dayone_products';

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'path_prefix',
        'is_active',
        'settings',
    ];

    public bool $isActive {
        get => (bool) $this->getAttribute('is_active');
    }

    /** @var array<string, mixed> */
    public array $settingsArray {
        get => (array) ($this->getAttribute('settings') ?? []);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    /** @return BelongsToMany<Model, $this> */
    public function users(): BelongsToMany
    {
        /** @var class-string<Model> $userModel */
        $userModel = config('auth.providers.users.model', 'App\\Models\\User');

        return $this->belongsToMany(
            related: $userModel,
            table: 'dayone_user_products',
        )->withPivot('role')->withTimestamps();
    }
}

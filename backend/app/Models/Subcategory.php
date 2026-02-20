<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subcategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'parent_subcategory_id',
        'depth',
        'code',
        'name',
        'labels',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'labels' => 'array',
        'is_active' => 'boolean',
        'depth' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_subcategory_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_subcategory_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function parameters(): HasMany
    {
        return $this->hasMany(LabParameter::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function label(string $locale, string $fallback = 'en'): string
    {
        return $this->labels[$locale]
            ?? $this->labels[$fallback]
            ?? $this->name;
    }
}

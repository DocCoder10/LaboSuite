<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discipline extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'labels',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'labels' => 'array',
        'is_active' => 'boolean',
    ];

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->orderBy('sort_order');
    }

    public function parameters(): HasMany
    {
        return $this->hasMany(LabParameter::class)->orderBy('sort_order');
    }

    public function label(string $locale, string $fallback = 'en'): string
    {
        return $this->labels[$locale]
            ?? $this->labels[$fallback]
            ?? $this->name;
    }
}

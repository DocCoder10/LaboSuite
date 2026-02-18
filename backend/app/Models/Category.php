<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'discipline_id',
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

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(Discipline::class);
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(Subcategory::class)->orderBy('sort_order');
    }

    public function parameters(): HasMany
    {
        return $this->hasMany(LabParameter::class)->orderBy('sort_order');
    }

    public function analyses(): BelongsToMany
    {
        return $this->belongsToMany(LabAnalysis::class, 'analysis_category', 'category_id', 'analysis_id')
            ->withTimestamps();
    }

    public function label(string $locale, string $fallback = 'en'): string
    {
        return $this->labels[$locale]
            ?? $this->labels[$fallback]
            ?? $this->name;
    }
}

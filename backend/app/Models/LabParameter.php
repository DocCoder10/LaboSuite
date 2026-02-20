<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabParameter extends Model
{
    use HasFactory;

    protected $fillable = [
        'discipline_id',
        'category_id',
        'subcategory_id',
        'code',
        'name',
        'labels',
        'unit',
        'value_type',
        'normal_min',
        'normal_max',
        'normal_text',
        'options',
        'default_value',
        'abnormal_style',
        'sort_order',
        'is_visible',
        'is_active',
    ];

    protected $casts = [
        'labels' => 'array',
        'options' => 'array',
        'abnormal_style' => 'array',
        'normal_min' => 'decimal:3',
        'normal_max' => 'decimal:3',
        'is_visible' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(Discipline::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(AnalysisResult::class);
    }

    public function label(string $locale, string $fallback = 'en'): string
    {
        return $this->labels[$locale]
            ?? $this->labels[$fallback]
            ?? $this->name;
    }

    public function referenceRange(): string
    {
        if ($this->normal_min !== null || $this->normal_max !== null) {
            $min = $this->normal_min !== null ? rtrim(rtrim((string) $this->normal_min, '0'), '.') : '-';
            $max = $this->normal_max !== null ? rtrim(rtrim((string) $this->normal_max, '0'), '.') : '-';

            return "{$min} - {$max}";
        }

        if ($this->normal_text) {
            return $this->normal_text;
        }

        return '-';
    }
}

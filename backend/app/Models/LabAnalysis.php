<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'analysis_number',
        'patient_id',
        'analysis_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'analysis_date' => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'analysis_category', 'analysis_id', 'category_id')
            ->withTimestamps();
    }

    public function results(): HasMany
    {
        return $this->hasMany(AnalysisResult::class, 'analysis_id');
    }
}

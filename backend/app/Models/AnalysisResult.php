<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalysisResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'analysis_id',
        'lab_parameter_id',
        'result_value',
        'result_numeric',
        'is_abnormal',
    ];

    protected $casts = [
        'result_numeric' => 'decimal:3',
        'is_abnormal' => 'boolean',
    ];

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(LabAnalysis::class, 'analysis_id');
    }

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(LabParameter::class, 'lab_parameter_id');
    }
}

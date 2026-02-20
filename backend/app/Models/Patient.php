<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'identifier',
        'first_name',
        'last_name',
        'sex',
        'age',
        'phone',
        'extra_fields',
    ];

    protected $casts = [
        'extra_fields' => 'array',
    ];

    public function analyses(): HasMany
    {
        return $this->hasMany(LabAnalysis::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->last_name} {$this->first_name}");
    }

    public function getDisplayIdentifierAttribute(): ?string
    {
        $publicIdentifier = trim((string) ($this->extra_fields['public_identifier'] ?? ''));

        if ($publicIdentifier !== '') {
            return $publicIdentifier;
        }

        $identifier = trim((string) $this->identifier);

        if ($identifier === '' || str_starts_with($identifier, 'SYS-')) {
            return null;
        }

        return $identifier;
    }
}

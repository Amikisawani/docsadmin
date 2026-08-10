<?php

namespace App\Domains\Signatures\Models;

use App\Domains\Users\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Signature extends Model
{
    use HasFactory;
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'label',
        'image_path',
        'certificate_data',
        'certificate_serial',
        'certificate_expires_at',
        'hash_algorithm',
        'is_default',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'certificate_expires_at' => 'datetime',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documentSignatures(): HasMany
    {
        return $this->hasMany(DocumentSignature::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}

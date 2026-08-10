<?php

namespace App\Domains\Archives\Models;

use App\Domains\Users\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArchiveBox extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'category',
        'description',
        'location',
        'capacity',
        'status',
        'created_by',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function archives(): HasMany
    {
        return $this->hasMany(Archive::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}

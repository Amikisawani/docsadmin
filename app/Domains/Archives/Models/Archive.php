<?php

namespace App\Domains\Archives\Models;

use App\Domains\Documents\Models\Document;
use App\Domains\Users\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Archive extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'document_id',
        'archive_box_id',
        'reference',
        'category',
        'conservation_duration',
        'archived_at',
        'conservation_until',
        'status',
        'notes',
        'archived_by',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
        'conservation_until' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function archiveBox(): BelongsTo
    {
        return $this->belongsTo(ArchiveBox::class);
    }

    public function archiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('conservation_until')
            ->where('conservation_until', '<=', now()->addDays($days))
            ->where('status', 'active');
    }
}
<?php

namespace App\Domains\Workflows\Models;

use App\Domains\Documents\Models\Document;
use App\Domains\Users\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowInstance extends Model
{
    use HasUlids;

    protected $fillable = [
        'workflow_id',
        'document_id',
        'current_step',
        'status',
        'history',
        'initiated_by',
        'completed_at',
    ];

    protected $casts = [
        'history' => 'json',
        'completed_at' => 'datetime',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(WorkflowApproval::class);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByCurrentStep($query, string $step)
    {
        return $query->where('current_step', $step);
    }
}
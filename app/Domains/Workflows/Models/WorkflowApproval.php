<?php

namespace App\Domains\Workflows\Models;

use App\Domains\Users\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowApproval extends Model
{
    use HasUlids;

    protected $fillable = [
        'workflow_instance_id',
        'step_name',
        'approver_id',
        'status',
        'comment',
        'signature_path',
        'action_at',
    ];

    protected $casts = [
        'action_at' => 'datetime',
    ];

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByApprover($query, string $userId)
    {
        return $query->where('approver_id', $userId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
<?php

namespace App\Domains\MailMerge\Models;

use App\Domains\Documents\Models\Document;
use App\Domains\Templates\Models\Template;
use App\Domains\Users\Models\User;
use App\Domains\Workflows\Models\Workflow;
use App\Domains\Workflows\Models\WorkflowInstance;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MailMergeBatch extends Model
{
use HasUlids;

    protected $fillable = [
        'template_id',
        'document_id',
        'workflow_id',
        'current_workflow_instance_id',
        'source_file_path',
        'recipients_file_path',
        'default_variables',
        'created_by',
        'title',
        'status',
        'total_recipients',
        'generated_count',
        'failed_count',
        'zip_path',
        'format',
        'errors',
        'completed_at',
        'submitted_for_signature_at',
        'signed_at',
        'signed_by',
        'signature_id',
        'signed_zip_path',
        'rejection_reason',
    ];

    protected $casts = [
        'total_recipients' => 'integer',
        'generated_count' => 'integer',
        'failed_count' => 'integer',
        'errors' => 'json',
        'default_variables' => 'json',
        'completed_at' => 'datetime',
        'submitted_for_signature_at' => 'datetime',
        'signed_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function currentWorkflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'current_workflow_instance_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(MailMergeRecipient::class, 'batch_id');
    }

    /** @return array<string, string> */
    public function getStatusLabelAttribute(): string
    {
return match ($this->status) {
            'processing' => 'En cours',
            'completed' => 'Terminé',
            'partial' => 'Partiel',
            'failed' => 'Échec',
            'awaiting_workflow' => 'En attente de validation',
            'pending_signature' => 'En attente de signature',
            'signed' => 'Signé',
            'rejected' => 'Rejeté',
            default => $this->status,
        };
    }
}


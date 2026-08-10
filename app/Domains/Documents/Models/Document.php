<?php

namespace App\Domains\Documents\Models;

use App\Domains\Archives\Models\Archive;
use App\Domains\Archives\Models\DocumentAttachment;
use App\Domains\Signatures\Models\DocumentSignature;
use App\Domains\Users\Models\User;
use App\Domains\Workflows\Models\Workflow;
use App\Domains\Workflows\Models\WorkflowInstance;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Document extends Model
{
    use HasUlids;
    use HasFactory;

    protected $fillable = [
        'document_number',
        'reference',
        'subject',
        'document_type',
        'author_id',
        'department_id',
        'version',
        'status',
        'flow_type',
        'is_mail_merge',
        'confidentiality',
        'document_date',
        'content',
        'workflow_id',
        'current_workflow_instance_id',
'source_file_path',
        'signed_pdf_path',
        'hash',
        'qr_code_path',
        'qr_anchor',
        'is_archived',
        'is_deleted',
        'submitted_for_signature_at',
        'priority',
        'deadline',
        'rejection_reason',
        'recalled_at',
    ];

    protected $casts = [
        'document_date' => 'date',
        'deadline' => 'date',
        'is_archived' => 'boolean',
        'is_deleted' => 'boolean',
        'is_mail_merge' => 'boolean',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Departments\Models\Department::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DocumentAttachment::class);
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(DocumentSignature::class);
    }

    public function workflowInstances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class);
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function currentWorkflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'current_workflow_instance_id');
    }

    public function archive(): HasOne
    {
        return $this->hasOne(Archive::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(DocumentHistory::class);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByConfidentiality($query, string $level)
    {
        return $query->where('confidentiality', $level);
    }

    public function scopeNotArchived($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('document_number', 'like', "%{$term}%")
                ->orWhere('reference', 'like', "%{$term}%")
                ->orWhere('subject', 'like', "%{$term}%");
        });
    }
}


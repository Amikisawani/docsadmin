<?php

namespace App\Domains\Users\Models;

use App\Domains\Archives\Models\Archive;
use App\Domains\Departments\Models\Department;
use App\Domains\Documents\Models\Document;
use App\Domains\Notifications\Models\NotificationPreference;
use App\Domains\Signatures\Models\Signature;
use App\Domains\Workflows\Models\WorkflowApproval;
use App\Domains\Workflows\Models\WorkflowInstance;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, HasUlids, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_path',
        'department_id',
        'job_title',
        'phone',
        'is_active',
        'signature_path',
        'signature_image_path',
        'status',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'two_factor_confirmed_at' => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'author_id');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(Signature::class);
    }

    public function workflowApprovals(): HasMany
    {
        return $this->hasMany(WorkflowApproval::class, 'approver_id');
    }

    public function initiatedWorkflows(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class, 'initiated_by');
    }

    public function archives(): HasMany
    {
        return $this->hasMany(Archive::class, 'archived_by');
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDepartment($query, string $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path ? asset('storage/'.$this->avatar_path) : null;
    }

    public function getSignatureUrlAttribute(): ?string
    {
        return $this->signature_image_path ? asset('storage/'.$this->signature_image_path) : null;
    }

    /**
     * Rôles autorisés à signer un document.
     *
     * Version Présidence : le Directeur de Cabinet est le SEUL détenteur du pouvoir de signature.
     * Ni l'administrateur, ni aucun autre rôle hiérarchique ne peut signer un document.
     */
    public static function signingRoles(): array
    {
        return ['directeur_cabinet'];
    }

    /**
     * L'utilisateur possède-t-il un rôle autorisé à signer les documents ?
     */
    public function canSignDocuments(): bool
    {
        return $this->roles()->whereIn('name', static::signingRoles())->exists();
    }
}

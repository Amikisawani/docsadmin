<?php

namespace App\Domains\Signatures\Models;

use App\Domains\Documents\Models\Document;
use App\Domains\Users\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSignature extends Model
{
    use HasUlids;

    protected $fillable = [
        'document_id',
        'signature_id',
        'signed_by',
        'type',
        'hash_signature',
        'certificate_chain',
        'signed_at',
        'ip_address',
        'user_agent',
        'position',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'position' => 'json',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function signature(): BelongsTo
    {
        return $this->belongsTo(Signature::class);
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }
}

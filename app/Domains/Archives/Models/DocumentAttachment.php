<?php

namespace App\Domains\Archives\Models;

use App\Domains\Documents\Models\Document;
use App\Domains\Users\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAttachment extends Model
{
    use HasUlids;

    protected $fillable = [
        'document_id',
        'original_name',
        'stored_path',
        'mime_type',
        'size',
        'hash',
        'type',
        'uploaded_by',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
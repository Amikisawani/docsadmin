<?php

namespace App\Domains\MailMerge\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailMergeRecipient extends Model
{
    use HasUlids;

    protected $fillable = [
        'batch_id',
        'name',
        'destinataire',
        'variables',
        'output_path',
        'status',
        'error',
        'generated_at',
    ];

    protected $casts = [
        'variables' => 'json',
        'generated_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(MailMergeBatch::class, 'batch_id');
    }
}

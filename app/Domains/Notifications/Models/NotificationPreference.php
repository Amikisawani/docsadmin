<?php

namespace App\Domains\Notifications\Models;

use App\Domains\Users\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasUlids;

    protected $fillable = [
        'user_id',
        'type',
        'email',
        'database',
        'sms',
        'push',
    ];

    protected $casts = [
        'email' => 'boolean',
        'database' => 'boolean',
        'sms' => 'boolean',
        'push' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

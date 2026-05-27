<?php

namespace App\Models;

use App\Enums\ContactMessageStatus;
use App\Enums\ContactSubject;
use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactMessage extends Model
{
    /** @use HasFactory<ContactMessageFactory> */
    use HasFactory;

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'subject',
        'message',
        'consented_at',
        'status',
        'handled_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'consented_at' => 'datetime',
            'subject' => ContactSubject::class,
            'status' => ContactMessageStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /** @return HasMany<ContactMessageReply, $this> */
    public function replies(): HasMany
    {
        return $this->hasMany(ContactMessageReply::class)->orderBy('sent_at');
    }
}

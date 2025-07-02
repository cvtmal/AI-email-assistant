<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EmailReply extends Model
{
    /** @use HasFactory<\Database\Factories\EmailReplyFactory> */
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'chat_history' => 'json',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email_id',
        'user_id',
        'account',
        'latest_ai_reply',
        'chat_history',
        'status',
        'sent_at',
        'failed_at',
        'error_message',
        'recipient_email',
        'subject',
    ];

    /**
     * Get the user that owns the email reply.
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get replies for a specific user and account.
     * @param \Illuminate\Database\Eloquent\Builder<EmailReply> $query
     * @return \Illuminate\Database\Eloquent\Builder<EmailReply>
     */
    public function scopeForUserAndAccount(\Illuminate\Database\Eloquent\Builder $query, int $userId, ?string $account = null): \Illuminate\Database\Eloquent\Builder
    {
        $query->where('user_id', $userId);

        if ($account) {
            $query->where('account', $account);
        }

        return $query;
    }

    /**
     * Scope to get recent activity for a user.
     * @param \Illuminate\Database\Eloquent\Builder<EmailReply> $query
     * @return \Illuminate\Database\Eloquent\Builder<EmailReply>
     */
    public function scopeRecentActivity(\Illuminate\Database\Eloquent\Builder $query, int $userId, int $limit = 10): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('user_id', $userId)
            ->whereNotNull('sent_at')
            ->orderBy('sent_at', 'desc')
            ->limit($limit);
    }

    /**
     * Get status badge color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'sent' => 'green',
            'failed' => 'red',
            'sending' => 'yellow',
            default => 'gray',
        };
    }

    /**
     * Get human-readable status.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'sent' => 'Sent',
            'failed' => 'Failed',
            'sending' => 'Sending',
            default => 'Unknown',
        };
    }
}

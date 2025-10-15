<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property string $email
 * @property string $role
 * @property string $status
 * @property string|null $message
 * @property string $token
 * @property int|null $inviter_id
 * @property int|null $organization_id
 * @property array<int, array<string, mixed>>|null $workspace_assignments
 * @property \Carbon\Carbon|null $accepted_at
 * @property \Carbon\Carbon|null $declined_at
 * @property \Carbon\Carbon|null $cancelled_at
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read User|null $inviter
 * @property-read Organization|null $organization
 */
class Invitation extends Model
{
    /** @use HasFactory<\Database\Factories\InvitationFactory> */
    use HasFactory;

    use HasUuid;

    protected $fillable = [
        'workspace_id',
        'organization_id',
        'email',
        'role',
        'status',
        'message',
        'token',
        'inviter_id',
        'workspace_assignments',
        'accepted_at',
        'declined_at',
        'cancelled_at',
        'expires_at',
    ];

    protected $casts = [
        'workspace_assignments' => 'array',
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'inviter_id',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Invitation $invitation): void {
            if (empty($invitation->token)) {
                $invitation->token = Str::random(64);
            }

            if (empty($invitation->expires_at)) {
                $invitation->expires_at = now()->addDays(7);
            }
        });
    }

    /**
     * Get the user who sent the invitation.
     */
    /**
     * @return BelongsTo<User, $this>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    /**
     * Get the organization that owns the invitation.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Check if the invitation is still valid.
     */
    public function isValid(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }

    /**
     * Check if the invitation has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the invitation has been accepted.
     */
    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    /**
     * Mark the invitation as accepted.
     */
    public function markAsAccepted(): void
    {
        $this->update(['accepted_at' => now()]);
    }

    /**
     * Generate a new token for the invitation.
     */
    public function regenerateToken(): void
    {
        $this->update([
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);
    }

    /**
     * Get the invitation acceptance URL.
     */
    public function getAcceptanceUrl(): string
    {
        return config('app.frontend_url').'/invitations/'.$this->token.'/accept';
    }

    /**
     * Scope to get only pending invitations.
     */
    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Invitation>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Invitation>
     */
    public function scopePending(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNull('accepted_at');
    }

    /**
     * Scope to get only valid invitations.
     */
    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Invitation>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Invitation>
     */
    public function scopeValid(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNull('accepted_at')
            ->where('expires_at', '>', now());
    }

    /**
     * Scope to get only expired invitations.
     */
    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Invitation>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Invitation>
     */
    public function scopeExpired(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNull('accepted_at')
            ->where('expires_at', '<', now());
    }

    /**
     * Scope to get invitations by email.
     */
    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Invitation>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Invitation>
     */
    public function scopeByEmail(\Illuminate\Database\Eloquent\Builder $query, string $email): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('email', $email);
    }

    /**
     * Scope to get invitations by token.
     */
    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Invitation>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Invitation>
     */
    public function scopeByToken(\Illuminate\Database\Eloquent\Builder $query, string $token): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('token', $token);
    }

    /**
     * Find invitation by token.
     */
    public static function findByToken(string $token): ?self
    {
        /** @var Invitation|null */
        return static::where('token', $token)->first();
    }

    /**
     * Find valid invitation by token.
     */
    public static function findValidByToken(string $token): ?self
    {
        /** @var Invitation|null */
        return static::valid()->where('token', $token)->first();
    }

    /**
     * Cancel the invitation.
     */
    public function cancel(): void
    {
        $this->update(['expires_at' => now()]);
    }

    /**
     * Resend the invitation with a new token.
     */
    public function resend(): void
    {
        $this->regenerateToken();
    }

    /**
     * Check if the user can accept this invitation.
     */
    public function canBeAcceptedBy(User $user): bool
    {
        return $this->email === $user->email && $this->isValid();
    }
}

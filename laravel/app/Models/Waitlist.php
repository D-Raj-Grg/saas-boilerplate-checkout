<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string $email
 * @property array<string, mixed>|null $metadata
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $referrer
 * @property string $status
 * @property \Carbon\Carbon|null $contacted_at
 * @property \Carbon\Carbon|null $converted_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 *
 * @method static \Database\Factories\WaitlistFactory factory(...$parameters)
 */
class Waitlist extends Model
{
    /** @use HasFactory<\Database\Factories\WaitlistFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'first_name',
        'last_name',
        'email',
        'metadata',
        'ip_address',
        'user_agent',
        'referrer',
        'status',
        'contacted_at',
        'converted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'contacted_at' => 'datetime',
        'converted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'id',
        'ip_address',
        'user_agent',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Waitlist $waitList): void {
            if (empty($waitList->uuid)) {
                $waitList->uuid = (string) Str::uuid();
            }

            // Capture request data if available
            try {
                $request = request();
                if (empty($waitList->ip_address)) {
                    $waitList->ip_address = $request->ip();
                }
                if (empty($waitList->user_agent)) {
                    $waitList->user_agent = $request->userAgent();
                }
                if (empty($waitList->referrer)) {
                    $waitList->referrer = $request->headers->get('referer');
                }
            } catch (\Throwable) {
                // Request not available (e.g., running in console)
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Get the full name attribute.
     */
    public function getFullNameAttribute(): ?string
    {
        if ($this->first_name || $this->last_name) {
            return trim($this->first_name.' '.$this->last_name);
        }

        return null;
    }

    /**
     * Scope a query to only include pending entries.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Waitlist>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Waitlist>
     */
    public function scopePending(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include contacted entries.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Waitlist>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Waitlist>
     */
    public function scopeContacted(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', 'contacted');
    }

    /**
     * Scope a query to only include converted entries.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Waitlist>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Waitlist>
     */
    public function scopeConverted(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', 'converted');
    }

    /**
     * Mark as contacted.
     */
    public function markAsContacted(): void
    {
        $this->update([
            'status' => 'contacted',
            'contacted_at' => now(),
        ]);
    }

    /**
     * Mark as converted.
     */
    public function markAsConverted(): void
    {
        $this->update([
            'status' => 'converted',
            'converted_at' => now(),
        ]);
    }
}

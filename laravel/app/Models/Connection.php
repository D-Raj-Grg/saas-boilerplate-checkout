<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class Connection extends Model
{
    /** @use HasFactory<\Database\Factories\ConnectionFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'workspace_id',
        'user_id',
        'integration_name',
        'site_url',
        'config',
        'status',
        'plugin_version',
        'last_sync_at',
    ];

    protected $casts = [
        'last_sync_at' => 'datetime',
    ];

    /**
     * @return Attribute<array<string, mixed>|null, array<string, mixed>|null>
     */
    protected function config(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? json_decode(Crypt::decryptString($value), true) : null,
            set: fn (?array $value) => $value ? Crypt::encryptString(json_encode($value) ?: '') : null,
        );
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function updateLastSync(): void
    {
        $this->update(['last_sync_at' => now()]);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function markAsError(): void
    {
        $this->update(['status' => 'error']);
    }

    public function markAsActive(): void
    {
        $this->update(['status' => 'active']);
    }
}

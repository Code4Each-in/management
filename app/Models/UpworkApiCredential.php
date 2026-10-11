<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Read-only Upwork API OAuth tokens (encrypted at rest; never shown or logged). */
class UpworkApiCredential extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['access_token', 'refresh_token'];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'expires_at' => 'datetime',
        'refresh_expires_at' => 'datetime',
        'last_refreshed_at' => 'datetime',
    ];

    /** Official Upwork API connection. */
    public static function current(): ?self
    {
        return static::forLabel('default');
    }

    /** Upwork MCP connection (tenant_id holds the org_uid searches run under). */
    public static function mcp(): ?self
    {
        return static::forLabel('mcp');
    }

    public static function forLabel(string $label): ?self
    {
        return static::where('label', $label)->first();
    }

    public function isConnected(): bool
    {
        return (bool) $this->refresh_token && (!$this->refresh_expires_at || $this->refresh_expires_at->isFuture());
    }
}

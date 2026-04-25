<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerTokenModel extends Model
{
    protected $table      = 'customer_tokens';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'customer_id',
        'token_hash',
        'label',
        'last_used_at',
        'expires_at',
    ];

    protected $useTimestamps = false; // created_at only, managed manually

    // Token TTL for frontend social-login sessions (days).
    private const TTL_DAYS = 30;

    // ── Issue & revoke ───────────────────────────────────────────────────

    /**
     * Issue a new plain-text token for a customer.
     *
     * The plain token is returned ONCE and never stored — only its SHA-256
     * hash is persisted. Treat the return value like a password.
     *
     * @param int         $customerId
     * @param string|null $label      e.g. 'google', 'facebook', 'manual'
     * @param bool        $expires    False for admin-issued permanent tokens.
     * @return string Plain token to send to the client.
     */
    public function issue(int $customerId, ?string $label = null, bool $expires = true): string
    {
        $plain = bin2hex(random_bytes(32)); // 64-char hex token

        $this->insert([
            'customer_id' => $customerId,
            'token_hash'  => hash('sha256', $plain),
            'label'       => $label,
            'expires_at'  => $expires
                ? date('Y-m-d H:i:s', strtotime('+' . self::TTL_DAYS . ' days'))
                : null,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        return $plain;
    }

    /**
     * Validate a plain token and return its customer_id if valid.
     *
     * Also touches last_used_at on success.
     *
     * @param string $plain
     * @return int|null customer_id or null if invalid/expired.
     */
    public function validate(string $plain): ?int
    {
        $hash   = hash('sha256', $plain);
        $record = $this->where('token_hash', $hash)
            ->groupStart()
                ->where('expires_at IS NULL')
                ->orWhere('expires_at >', date('Y-m-d H:i:s'))
            ->groupEnd()
            ->first();

        if (!$record) {
            return null;
        }

        // Touch last_used_at without triggering full model validation.
        $this->db->table($this->table)
            ->where('id', $record->id)
            ->update(['last_used_at' => date('Y-m-d H:i:s')]);

        return (int) $record->customer_id;
    }

    /**
     * Revoke a single token by its plain value.
     *
     * @param string $plain
     * @return bool
     */
    public function revoke(string $plain): bool
    {
        return $this->where('token_hash', hash('sha256', $plain))->delete() > 0;
    }

    /**
     * Revoke ALL tokens for a customer (e.g. on ban or forced logout).
     *
     * @param int $customerId
     * @return bool
     */
    public function revokeAll(int $customerId): bool
    {
        return $this->where('customer_id', $customerId)->delete() > 0;
    }

    /**
     * Purge all expired tokens. Run this on a scheduled task.
     *
     * @return int Number of deleted rows.
     */
    public function purgeExpired(): int
    {
        return $this->where('expires_at <', date('Y-m-d H:i:s'))
            ->where('expires_at IS NOT NULL')
            ->delete();
    }
}

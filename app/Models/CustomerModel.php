<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerModel extends Model
{
    protected $table      = 'customers';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'wp_user_id',
        'google_id',
        'facebook_id',
        'email',
        'name',
        'phone',
        'avatar_url',
        'billing_address',
        'status',
        'source',
        'last_login_at',
    ];

    protected $useTimestamps = true;

    protected $validationRules = [
        'email'  => 'required|valid_email|max_length[255]',
        'name'   => 'required|max_length[255]',
        'status' => 'in_list[active,inactive,banned]',
        'source' => 'in_list[wp_import,google,facebook,manual]',
    ];

    // ── Finders ──────────────────────────────────────────────────────────

    /**
     * Find a customer by email address.
     *
     * @param string $email
     * @return object|null
     */
    public function findByEmail(string $email): ?object
    {
        return $this->where('email', $email)->first();
    }

    /**
     * Find a customer by their WooCommerce user id.
     *
     * @param int $wpUserId
     * @return object|null
     */
    public function findByWpId(int $wpUserId): ?object
    {
        return $this->where('wp_user_id', $wpUserId)->first();
    }

    /**
     * Find a customer by their Google sub/id.
     *
     * @param string $googleId
     * @return object|null
     */
    public function findByGoogleId(string $googleId): ?object
    {
        return $this->where('google_id', $googleId)->first();
    }

    /**
     * Find a customer by their Facebook user id.
     *
     * @param string $facebookId
     * @return object|null
     */
    public function findByFacebookId(string $facebookId): ?object
    {
        return $this->where('facebook_id', $facebookId)->first();
    }

    // ── WP sync ──────────────────────────────────────────────────────────

    /**
     * Upsert a customer from a WooCommerce webhook payload.
     *
     * Matching priority: wp_user_id → email.
     * This means if a customer changes their email on WP, we still find them
     * by wp_user_id and update the email correctly.
     *
     * @param array $data Normalised WP customer payload.
     * @return int Internal customer id.
     */
    public function upsertFromWp(array $data): int
    {
        $existing = null;

        if (!empty($data['wp_user_id'])) {
            $existing = $this->findByWpId((int) $data['wp_user_id']);
        }

        if (!$existing && !empty($data['email'])) {
            $existing = $this->findByEmail($data['email']);
        }

        $payload = $this->buildWpPayload($data);

        if ($existing) {
            $this->update($existing->id, $payload);
            return $existing->id;
        }

        $payload['source'] = 'wp_import';
        return $this->insert($payload, true);
    }

    /**
     * Build a clean, safe payload from a raw WP customer array.
     *
     * @param array $data
     * @return array
     */
    private function buildWpPayload(array $data): array
    {
        $payload = [
            'email' => strtolower(trim($data['email'])),
            'name'  => html_entity_decode($data['name'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        ];

        if (!empty($data['wp_user_id'])) {
            $payload['wp_user_id'] = (int) $data['wp_user_id'];
        }
        if (!empty($data['phone'])) {
            $payload['phone'] = $data['phone'];
        }
        if (!empty($data['avatar_url'])) {
            $payload['avatar_url'] = $data['avatar_url'];
        }
        if (!empty($data['billing_address']) && is_array($data['billing_address'])) {
            $payload['billing_address'] = json_encode($data['billing_address']);
        }
        if (isset($data['google_id'])) {
            $payload['google_id'] = $data['google_id'];
        }
        if (isset($data['facebook_id'])) {
            $payload['facebook_id'] = $data['facebook_id'];
        }

        return $payload;
    }

    // ── Social login ─────────────────────────────────────────────────────

    /**
     * Find or create a customer from a verified Google profile.
     *
     * Matching priority: google_id → email.
     * If found by email, we attach the google_id so future logins are faster.
     *
     * @param array $profile Verified profile: {id, email, name, avatar_url}
     * @return object Customer record.
     */
    public function findOrCreateFromGoogle(array $profile): object
    {
        // 1. Match by google_id (fastest, most reliable)
        $customer = $this->findByGoogleId($profile['id']);

        // 2. Match by email — attach google_id for next time
        if (!$customer) {
            $customer = $this->findByEmail($profile['email']);
            if ($customer) {
                $this->update($customer->id, ['google_id' => $profile['id']]);
                $customer->google_id = $profile['id'];
            }
        }

        // 3. New customer
        if (!$customer) {
            $id = $this->insert([
                'google_id'  => $profile['id'],
                'email'      => strtolower(trim($profile['email'])),
                'name'       => $profile['name'],
                'avatar_url' => $profile['avatar_url'] ?? null,
                'source'     => 'google',
                'status'     => 'active',
            ], true);

            $customer = $this->find($id);
        }

        return $customer;
    }

    /**
     * Find or create a customer from a verified Facebook profile.
     *
     * @param array $profile Verified profile: {id, email, name, avatar_url}
     * @return object Customer record.
     */
    public function findOrCreateFromFacebook(array $profile): object
    {
        $customer = $this->findByFacebookId($profile['id']);

        if (!$customer && !empty($profile['email'])) {
            $customer = $this->findByEmail($profile['email']);
            if ($customer) {
                $this->update($customer->id, ['facebook_id' => $profile['id']]);
                $customer->facebook_id = $profile['id'];
            }
        }

        if (!$customer) {
            $id = $this->insert([
                'facebook_id' => $profile['id'],
                'email'       => strtolower(trim($profile['email'] ?? '')),
                'name'        => $profile['name'],
                'avatar_url'  => $profile['avatar_url'] ?? null,
                'source'      => 'facebook',
                'status'      => 'active',
            ], true);

            $customer = $this->find($id);
        }

        return $customer;
    }

    // ── Dashboard queries ─────────────────────────────────────────────────

    /**
     * Paginated customer list with optional search and status filter.
     *
     * @param int         $page
     * @param int         $perPage
     * @param string|null $search  Searches name and email.
     * @param string|null $status  'active'|'inactive'|'banned'
     * @return array{data: object[], total: int, pages: int}
     */
    public function paginated(int $page = 1, int $perPage = 25, ?string $search = null, ?string $status = null): array
    {
        $builder = $this->builder();

        if ($search) {
            $safe = $this->db->escapeLikeString($search);
            $builder->groupStart()
                ->like('name', $safe)
                ->orLike('email', $safe)
                ->groupEnd();
        }

        if ($status) {
            $builder->where('status', $status);
        }

        $total = (clone $builder)->countAllResults(false);
        $data  = $builder
            ->orderBy('created_at', 'DESC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->get()
            ->getResult();

        return [
            'data'  => $data,
            'total' => $total,
            'pages' => (int) ceil($total / $perPage),
        ];
    }
}

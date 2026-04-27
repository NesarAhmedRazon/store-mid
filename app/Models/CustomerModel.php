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
        'password',
        'name',
        'phone',
        'avatar_url',
        'billing_address',
        'login_methods',
        'status',
        'source',
        'last_login_at',
        'last_active_at',
    ];

    protected $useTimestamps = true;

    protected $validationRules = [
        'email'  => 'required|valid_email|max_length[255]',
        'name'   => 'required|max_length[255]',
        'status' => 'in_list[active,inactive,banned]',
        'source' => 'in_list[wp_import,google,facebook,manual,email]',
    ];

    // ── Login methods helpers ─────────────────────────────────────────────

    /**
     * Decode the JSON login_methods column into an array.
     * Safe to call on any customer object — returns [] if null.
     */
    public function getLoginMethods(object $customer): array
    {
        if (empty($customer->login_methods)) {
            return [];
        }
        $decoded = json_decode($customer->login_methods, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Add a login method to the customer's login_methods list if not present.
     * Writes to DB immediately.
     *
     * @param int    $customerId
     * @param string $method  'email'|'google'|'facebook'
     */
    public function addLoginMethod(int $customerId, string $method): void
    {
        $customer = $this->find($customerId);
        if (!$customer) return;

        $methods = $this->getLoginMethods($customer);

        if (!in_array($method, $methods, true)) {
            $methods[] = $method;
            $this->db->table($this->table)
                ->where('id', $customerId)
                ->update(['login_methods' => json_encode(array_values($methods))]);
        }
    }

    /**
     * Check if a customer has a specific login method.
     */
    public function hasLoginMethod(object $customer, string $method): bool
    {
        return in_array($method, $this->getLoginMethods($customer), true);
    }

    // ── Finders ──────────────────────────────────────────────────────────

    public function findByEmail(string $email): ?object
    {
        return $this->where('email', strtolower(trim($email)))->first();
    }

    public function findByWpId(int $wpUserId): ?object
    {
        return $this->where('wp_user_id', $wpUserId)->first();
    }

    public function findByGoogleId(string $googleId): ?object
    {
        return $this->where('google_id', $googleId)->first();
    }

    public function findByFacebookId(string $facebookId): ?object
    {
        return $this->where('facebook_id', $facebookId)->first();
    }

    // ── Email auth ───────────────────────────────────────────────────────

    /**
     * Register a new customer with email + password.
     *
     * Returns the new customer object, or throws on duplicate email.
     *
     * @throws \RuntimeException if email already exists.
     */
    public function registerWithEmail(string $email, string $password, string $name): object
    {
        $email = strtolower(trim($email));

        if ($this->findByEmail($email)) {
            throw new \RuntimeException('email_taken');
        }

        $id = $this->insert([
            'email'         => $email,
            'password'      => password_hash($password, PASSWORD_BCRYPT),
            'name'          => $name,
            'source'        => 'email',
            'status'        => 'active',
            'login_methods' => json_encode(['email']),
        ], true);

        return $this->find($id);
    }

    /**
     * Verify email + password and return the customer on success.
     *
     * Returns null for wrong credentials or missing password column (social-only account).
     * Does NOT check status — let the controller decide the response for inactive/banned.
     */
    public function verifyEmailLogin(string $email, string $password): ?object
    {
        $customer = $this->findByEmail($email);

        if (!$customer || empty($customer->password)) {
            return null;
        }

        if (!password_verify($password, $customer->password)) {
            return null;
        }

        return $customer;
    }

    // ── WP sync ──────────────────────────────────────────────────────────

    /**
     * Upsert a customer from a WooCommerce webhook payload.
     * Matching priority: wp_user_id → email.
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
            // Merge login_methods — preserve any methods the customer already has
            $existingMethods = $this->getLoginMethods($existing);
            if (!in_array('wp_import', $existingMethods, true)) {
                $existingMethods[] = 'wp_import';
                $payload['login_methods'] = json_encode(array_values($existingMethods));
            }

            $this->update($existing->id, $payload);
            return $existing->id;
        }
        log_message('info', print_r($payload, true));
        $payload['source']        = 'wp_import';
        $payload['status']        = 'active';
        $payload['login_methods'] = json_encode(['wp_import']);
        return $this->insert($payload, true);
    }

    private function buildWpPayload(array $data): array
    {
        $payload = [
            'email' => strtolower(trim($data['email'])),
            'name'  => html_entity_decode($data['name'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        ];

        // Add optional fields if they exist
        if (!empty($data['wp_user_id'])) {
            $payload['wp_user_id'] = (int) $data['wp_user_id'];
        }

        if (!empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        if (!empty($data['phone'])) {
            $payload['phone'] = $data['phone'];
        }

        if (!empty($data['avatar_url'])) {
            $payload['avatar_url'] = $data['avatar_url'];
        }

        // Handle billing_address - convert array to JSON
        if (!empty($data['billing_address']) && is_array($data['billing_address'])) {
            $payload['billing_address'] = json_encode($data['billing_address']);
        } elseif (!empty($data['billing_address']) && is_string($data['billing_address'])) {
            $payload['billing_address'] = $data['billing_address'];
        }

        // Handle social IDs (allow null/empty values)
        if (isset($data['google_id'])) {
            $payload['google_id'] = !empty($data['google_id']) ? $data['google_id'] : null;
        }

        if (isset($data['facebook_id'])) {
            $payload['facebook_id'] = !empty($data['facebook_id']) ? $data['facebook_id'] : null;
        }

        // Set status if provided, otherwise default to active
        if (isset($data['status'])) {
            $payload['status'] = $data['status'];
        }

        return $payload;
    }

    // ── Social login ─────────────────────────────────────────────────────

    /**
     * Find or create from a verified Google profile.
     * Matching priority: google_id → email.
     * Attaches google_id and adds 'google' to login_methods on email match.
     */
    public function findOrCreateFromGoogle(array $profile): object
    {
        $customer = $this->findByGoogleId($profile['id']);

        if (!$customer) {
            $customer = $this->findByEmail($profile['email']);
            if ($customer) {
                // Existing account — attach Google
                $this->db->table($this->table)->where('id', $customer->id)->update([
                    'google_id' => $profile['id'],
                ]);
                $this->addLoginMethod($customer->id, 'google');
                $customer = $this->find($customer->id);
            }
        }

        if (!$customer) {
            $id = $this->insert([
                'google_id'     => $profile['id'],
                'email'         => strtolower(trim($profile['email'])),
                'name'          => $profile['name'],
                'avatar_url'    => $profile['avatar_url'] ?? null,
                'source'        => 'google',
                'status'        => 'active',
                'login_methods' => json_encode(['google']),
            ], true);

            $customer = $this->find($id);
        }

        return $customer;
    }

    /**
     * Find or create from a verified Facebook profile.
     * Matching priority: facebook_id → email.
     */
    public function findOrCreateFromFacebook(array $profile): object
    {
        $customer = $this->findByFacebookId($profile['id']);

        if (!$customer && !empty($profile['email'])) {
            $customer = $this->findByEmail($profile['email']);
            if ($customer) {
                $this->db->table($this->table)->where('id', $customer->id)->update([
                    'facebook_id' => $profile['id'],
                ]);
                $this->addLoginMethod($customer->id, 'facebook');
                $customer = $this->find($customer->id);
            }
        }

        if (!$customer) {
            $id = $this->insert([
                'facebook_id'   => $profile['id'],
                'email'         => strtolower(trim($profile['email'] ?? '')),
                'name'          => $profile['name'],
                'avatar_url'    => $profile['avatar_url'] ?? null,
                'source'        => 'facebook',
                'status'        => 'active',
                'login_methods' => json_encode(['facebook']),
            ], true);

            $customer = $this->find($id);
        }

        return $customer;
    }

    // ── Activity tracking ─────────────────────────────────────────────────

    /**
     * Touch last_active_at for a customer.
     * Called on every authenticated API request via CustomerAuthFilter.
     * Uses raw query to bypass model validation/timestamps overhead.
     */
    public function touchActivity(int $customerId): void
    {
        $this->db->table($this->table)
            ->where('id', $customerId)
            ->update(['last_active_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Mark customers as inactive if they haven't been active for 90+ days.
     *
     * IMPORTANT: Does NOT affect login — inactive customers can still log in.
     * Only marks status for dashboard visibility / reporting purposes.
     * Run this from a scheduled command.
     *
     * @return int Number of customers marked inactive.
     */
    public function markInactiveAfter90Days(): int
    {
        $cutoff = date('Y-m-d H:i:s', strtotime('-90 days'));

        return $this->db->table($this->table)
            ->where('status', 'active')
            ->where('last_active_at <', $cutoff)
            ->where('last_active_at IS NOT NULL')
            ->update(['status' => 'inactive']);
    }

    // ── Dashboard queries ─────────────────────────────────────────────────

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

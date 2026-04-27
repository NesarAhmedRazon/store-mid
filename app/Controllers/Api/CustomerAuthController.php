<?php

namespace App\Controllers\Api;

use App\Models\CustomerModel;
use App\Models\CustomerTokenModel;
use CodeIgniter\RESTful\ResourceController;

/**
 * CustomerAuthController
 *
 * Public endpoints:
 *   POST /api/customer/auth/register   — email + password registration
 *   POST /api/customer/auth/login      — email + password login
 *   POST /api/customer/auth/google     — Google id_token login
 *   POST /api/customer/auth/facebook   — Facebook access_token login
 *   POST /api/customer/auth/logout     — revoke current token
 *
 * Protected endpoints (Bearer token via CustomerAuthFilter):
 *   GET  /api/get/customer/me
 *   PUT  /api/get/customer/me
 *   GET  /api/get/customer/me/orders
 */
class CustomerAuthController extends ResourceController
{
    protected string $format = 'json';

    private CustomerModel      $customers;
    private CustomerTokenModel $tokens;

    private const GOOGLE_TOKEN_INFO = 'https://oauth2.googleapis.com/tokeninfo?id_token=';
    private const FB_GRAPH          = 'https://graph.facebook.com/me?fields=id,name,email,picture&access_token=';

    public function __construct()
    {
        $this->customers = new CustomerModel();
        $this->tokens    = new CustomerTokenModel();
    }

    // ═══════════════════════════════════════════════════════════════════
    // Email auth
    // ═══════════════════════════════════════════════════════════════════

    /**
     * POST /api/customer/auth/register
     *
     * Body: { "email": "", "password": "", "name": "" }
     *
     * - If email exists with no password (social-only account) → attach email login
     * - If email exists WITH a password → 409 conflict
     */
    public function register()
    {
        $body = $this->request->getJSON(true);

        $email    = strtolower(trim($body['email']    ?? ''));
        $password = $body['password'] ?? '';
        $name     = trim($body['name'] ?? '');

        if (!$email || !$password || !$name) {
            return $this->fail('email, password and name are required.', 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->fail('Invalid email address.', 422);
        }

        if (strlen($password) < 8) {
            return $this->fail('Password must be at least 8 characters.', 422);
        }

        $existing = $this->customers->findByEmail($email);

        if ($existing) {
            if (!empty($existing->password)) {
                return $this->fail('An account with this email already exists.', 409);
            }

            // Social/WP account exists — attach email login to it
            $this->db->table('customers')
                ->where('id', $existing->id)
                ->update(['password' => password_hash($password, PASSWORD_BCRYPT)]);
            $this->customers->addLoginMethod($existing->id, 'email');
            $customer = $this->customers->find($existing->id);

        } else {
            try {
                $customer = $this->customers->registerWithEmail($email, $password, $name);
            } catch (\RuntimeException $e) {
                return $this->fail('An account with this email already exists.', 409);
            }
        }

        $this->customers->update($customer->id, ['last_login_at' => date('Y-m-d H:i:s')]);
        $this->customers->touchActivity($customer->id);

        $token = $this->tokens->issue($customer->id, 'email');

        return $this->respondCreated([
            'token'    => $token,
            'customer' => $this->formatCustomer($this->customers->find($customer->id)),
        ]);
    }

    /**
     * POST /api/customer/auth/login
     *
     * Body: { "email": "", "password": "" }
     *
     * - Inactive customers CAN log in (login re-activates them)
     * - Banned customers are blocked
     * - Social-only accounts get a helpful hint about which method to use
     */
    public function login()
    {
        $body = $this->request->getJSON(true);

        $email    = strtolower(trim($body['email']    ?? ''));
        $password = $body['password'] ?? '';

        if (!$email || !$password) {
            return $this->fail('email and password are required.', 422);
        }

        $customer = $this->customers->verifyEmailLogin($email, $password);

        if (!$customer) {
            // Check if account exists but is social-only — give a helpful message
            $exists = $this->customers->findByEmail($email);
            if ($exists && empty($exists->password)) {
                $methods = array_filter(
                    $this->customers->getLoginMethods($exists),
                    fn($m) => $m !== 'email'
                );
                $hint = !empty($methods) ? implode(' or ', $methods) : 'social login';
                return $this->failUnauthorized("This account uses {$hint}. Please sign in with that method.");
            }

            return $this->failUnauthorized('Invalid email or password.');
        }

        if ($customer->status === 'banned') {
            return $this->failForbidden('Your account has been suspended.');
        }

        // Re-activate if inactive — logging in is explicit activity
        $updateData = ['last_login_at' => date('Y-m-d H:i:s')];
        if ($customer->status === 'inactive') {
            $updateData['status'] = 'active';
        }

        $this->customers->update($customer->id, $updateData);
        $this->customers->touchActivity($customer->id);
        $this->customers->addLoginMethod($customer->id, 'email');

        $token = $this->tokens->issue($customer->id, 'email');

        return $this->respond([
            'token'    => $token,
            'customer' => $this->formatCustomer($this->customers->find($customer->id)),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Social auth
    // ═══════════════════════════════════════════════════════════════════

    /**
     * POST /api/customer/auth/google
     * Body: { "id_token": "<google id_token from frontend>" }
     */
    public function google()
    {
        $idToken = $this->request->getJSON()->id_token ?? null;

        if (!$idToken) {
            return $this->fail('id_token is required.', 422);
        }

        $profile = $this->verifyGoogleToken($idToken);
        if (!$profile) {
            return $this->failUnauthorized('Invalid or expired Google token.');
        }

        $customer = $this->customers->findOrCreateFromGoogle($profile);

        if ($customer->status === 'banned') {
            return $this->failForbidden('Your account has been suspended.');
        }

        $updateData = ['last_login_at' => date('Y-m-d H:i:s')];
        if ($customer->status === 'inactive') {
            $updateData['status'] = 'active';
        }

        $this->customers->update($customer->id, $updateData);
        $this->customers->touchActivity($customer->id);

        $token = $this->tokens->issue($customer->id, 'google');

        return $this->respond([
            'token'    => $token,
            'customer' => $this->formatCustomer($this->customers->find($customer->id)),
        ]);
    }

    /**
     * POST /api/customer/auth/facebook
     * Body: { "access_token": "<facebook user access_token from frontend>" }
     */
    public function facebook()
    {
        $accessToken = $this->request->getJSON()->access_token ?? null;

        if (!$accessToken) {
            return $this->fail('access_token is required.', 422);
        }

        $profile = $this->verifyFacebookToken($accessToken);
        if (!$profile) {
            return $this->failUnauthorized('Invalid or expired Facebook token.');
        }

        $customer = $this->customers->findOrCreateFromFacebook($profile);

        if ($customer->status === 'banned') {
            return $this->failForbidden('Your account has been suspended.');
        }

        $updateData = ['last_login_at' => date('Y-m-d H:i:s')];
        if ($customer->status === 'inactive') {
            $updateData['status'] = 'active';
        }

        $this->customers->update($customer->id, $updateData);
        $this->customers->touchActivity($customer->id);

        $token = $this->tokens->issue($customer->id, 'facebook');

        return $this->respond([
            'token'    => $token,
            'customer' => $this->formatCustomer($this->customers->find($customer->id)),
        ]);
    }

    /**
     * POST /api/customer/auth/logout
     */
    public function logout()
    {
        $plain = $this->extractBearerToken();

        if (!$plain) {
            return $this->fail('No token provided.', 422);
        }

        $this->tokens->revoke($plain);

        return $this->respond(['message' => 'Logged out successfully.']);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Protected customer endpoints
    // ═══════════════════════════════════════════════════════════════════

    /** GET /api/get/customer/me */
    public function me()
    {
        $customer = $this->getAuthenticatedCustomer();
        if (!$customer) return $this->failUnauthorized();

        $this->customers->touchActivity($customer->id);

        return $this->respond($this->formatCustomer($customer));
    }

    /**
     * PUT /api/get/customer/me
     * Customers can update name, phone, billing_address only.
     */
    public function updateMe()
    {
        $customer = $this->getAuthenticatedCustomer();
        if (!$customer) return $this->failUnauthorized();

        $data    = $this->request->getJSON(true);
        $allowed = ['name', 'phone', 'billing_address'];
        $payload = array_intersect_key($data, array_flip($allowed));

        if (empty($payload)) {
            return $this->fail('No updatable fields provided.', 422);
        }

        if (isset($payload['billing_address']) && is_array($payload['billing_address'])) {
            $payload['billing_address'] = json_encode($payload['billing_address']);
        }

        if (!$this->customers->update($customer->id, $payload)) {
            return $this->fail($this->customers->errors(), 422);
        }

        $this->customers->touchActivity($customer->id);

        return $this->respond($this->formatCustomer($this->customers->find($customer->id)));
    }

    /** GET /api/get/customer/me/orders */
    public function myOrders()
    {
        $customer = $this->getAuthenticatedCustomer();
        if (!$customer) return $this->failUnauthorized();

        $this->customers->touchActivity($customer->id);

        // TODO: return (new OrderModel())->getByCustomer($customer->id);
        return $this->respond(['orders' => [], 'message' => 'Order system not yet connected.']);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Private helpers
    // ═══════════════════════════════════════════════════════════════════

    private function verifyGoogleToken(string $idToken): ?array
    {
        $url      = self::GOOGLE_TOKEN_INFO . urlencode($idToken);
        $response = @file_get_contents($url);
        if (!$response) return null;

        $data = json_decode($response, true);
        if (empty($data['sub']) || empty($data['email'])) return null;

        $expectedAudience = env('GOOGLE_CLIENT_ID');
        if ($expectedAudience && ($data['aud'] ?? '') !== $expectedAudience) return null;

        return [
            'id'         => $data['sub'],
            'email'      => strtolower($data['email']),
            'name'       => $data['name'] ?? '',
            'avatar_url' => $data['picture'] ?? null,
        ];
    }

    private function verifyFacebookToken(string $accessToken): ?array
    {
        $url      = self::FB_GRAPH . urlencode($accessToken);
        $response = @file_get_contents($url);
        if (!$response) return null;

        $data = json_decode($response, true);
        if (empty($data['id'])) return null;

        return [
            'id'         => $data['id'],
            'email'      => strtolower($data['email'] ?? ''),
            'name'       => $data['name'] ?? '',
            'avatar_url' => $data['picture']['data']['url'] ?? null,
        ];
    }

    private function extractBearerToken(): ?string
    {
        $header = $this->request->getHeaderLine('Authorization');
        if (preg_match('/^Bearer\s+(\S+)$/i', $header, $m)) {
            return $m[1];
        }
        return null;
    }

    private function getAuthenticatedCustomer(): ?object
    {
        $plain = $this->extractBearerToken();
        if (!$plain) return null;

        $customerId = $this->tokens->validateToken($plain);
        if (!$customerId) return null;

        $customer = $this->customers->find($customerId);

        // Banned = hard block. Inactive = still allowed (they can log in)
        if (!$customer || $customer->status === 'banned') return null;

        return $customer;
    }

    private function formatCustomer(object $customer): array
    {
        return [
            'id'              => (int) $customer->id,
            'name'            => $customer->name,
            'email'           => $customer->email,
            'phone'           => $customer->phone,
            'avatar_url'      => $customer->avatar_url,
            'billing_address' => $customer->billing_address
                ? json_decode($customer->billing_address, true)
                : null,
            'source'          => $customer->source,
            'login_methods'   => $this->customers->getLoginMethods($customer),
            'status'          => $customer->status,
            'last_login_at'   => $customer->last_login_at,
            'created_at'      => $customer->created_at,
        ];
    }
}

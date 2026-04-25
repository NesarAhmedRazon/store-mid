<?php

namespace App\Controllers\Api;

use App\Models\CustomerModel;
use App\Models\CustomerTokenModel;
use CodeIgniter\RESTful\ResourceController;

/**
 * CustomerAuthController
 *
 * Handles all frontend-facing customer endpoints.
 *
 * Public endpoints:
 *   POST /customer/auth/google
 *   POST /customer/auth/facebook
 *   POST /customer/auth/logout
 *
 * Protected endpoints (require Bearer token):
 *   GET  /customer/me
 *   PUT  /customer/me
 *   GET  /customer/me/orders  (stub — wire to your OrderModel)
 */
class CustomerAuthController extends ResourceController
{
    protected string $format = 'json';

    private CustomerModel      $customers;
    private CustomerTokenModel $tokens;

    // Google token-info endpoint (no SDK needed).
    private const GOOGLE_TOKEN_INFO = 'https://oauth2.googleapis.com/tokeninfo?id_token=';

    // Facebook graph endpoint.
    private const FB_GRAPH = 'https://graph.facebook.com/me?fields=id,name,email,picture&access_token=';

    public function __construct()
    {
        $this->customers = new CustomerModel();
        $this->tokens    = new CustomerTokenModel();
    }

    // ═══════════════════════════════════════════════════════════════════
    // Social Auth
    // ═══════════════════════════════════════════════════════════════════

    /**
     * POST /customer/auth/google
     *
     * Body: { "id_token": "<google id_token from frontend>" }
     *
     * Flow:
     *   1. Verify id_token with Google's tokeninfo endpoint.
     *   2. Find or create customer.
     *   3. Issue session token.
     *   4. Return customer + token.
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

        if ($customer->status !== 'active') {
            return $this->failForbidden('Your account is not active.');
        }

        $this->customers->update($customer->id, ['last_login_at' => date('Y-m-d H:i:s')]);

        $token = $this->tokens->issue($customer->id, 'google');

        return $this->respond([
            'token'    => $token,
            'customer' => $this->formatCustomer($customer),
        ]);
    }

    /**
     * POST /customer/auth/facebook
     *
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

        if ($customer->status !== 'active') {
            return $this->failForbidden('Your account is not active.');
        }

        $this->customers->update($customer->id, ['last_login_at' => date('Y-m-d H:i:s')]);

        $token = $this->tokens->issue($customer->id, 'facebook');

        return $this->respond([
            'token'    => $token,
            'customer' => $this->formatCustomer($customer),
        ]);
    }

    /**
     * POST /customer/auth/logout
     *
     * Revokes the current token. Body or header: Bearer token.
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
    // Protected customer endpoints  (use CustomerAuthFilter)
    // ═══════════════════════════════════════════════════════════════════

    /**
     * GET /customer/me
     *
     * Returns the authenticated customer's profile.
     */
    public function me()
    {
        $customer = $this->getAuthenticatedCustomer();
        if (!$customer) {
            return $this->failUnauthorized();
        }

        return $this->respond($this->formatCustomer($customer));
    }

    /**
     * PUT /customer/me
     *
     * Allows the customer to update their own name, phone, billing_address.
     * They cannot change email, status, or source via this endpoint.
     */
    public function updateMe()
    {
        $customer = $this->getAuthenticatedCustomer();
        if (!$customer) {
            return $this->failUnauthorized();
        }

        $data = $this->request->getJSON(true);

        // Whitelist — customers can only touch these fields.
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

        return $this->respond($this->formatCustomer($this->customers->find($customer->id)));
    }

    /**
     * GET /customer/me/orders
     *
     * Stub — wire this to your OrderModel when ready.
     */
    public function myOrders()
    {
        $customer = $this->getAuthenticatedCustomer();
        if (!$customer) {
            return $this->failUnauthorized();
        }

        // TODO: return (new OrderModel())->getByCustomer($customer->id);
        return $this->respond(['orders' => [], 'message' => 'Order system not yet connected.']);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Private helpers
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Verify a Google id_token via Google's tokeninfo endpoint.
     *
     * Returns a normalised profile array or null on failure.
     *
     * @param string $idToken
     * @return array|null {id, email, name, avatar_url}
     */
    private function verifyGoogleToken(string $idToken): ?array
    {
        $url      = self::GOOGLE_TOKEN_INFO . urlencode($idToken);
        $response = @file_get_contents($url);

        if (!$response) {
            return null;
        }

        $data = json_decode($response, true);

        // Validate audience matches our client id.
        if (empty($data['sub']) || empty($data['email'])) {
            return null;
        }

        $expectedAudience = env('GOOGLE_CLIENT_ID');
        if ($expectedAudience && $data['aud'] !== $expectedAudience) {
            return null;
        }

        return [
            'id'         => $data['sub'],
            'email'      => strtolower($data['email']),
            'name'       => $data['name'] ?? '',
            'avatar_url' => $data['picture'] ?? null,
        ];
    }

    /**
     * Verify a Facebook user access_token via Graph API.
     *
     * Returns a normalised profile array or null on failure.
     *
     * @param string $accessToken
     * @return array|null {id, email, name, avatar_url}
     */
    private function verifyFacebookToken(string $accessToken): ?array
    {
        $url      = self::FB_GRAPH . urlencode($accessToken);
        $response = @file_get_contents($url);

        if (!$response) {
            return null;
        }

        $data = json_decode($response, true);

        if (empty($data['id'])) {
            return null;
        }

        return [
            'id'         => $data['id'],
            'email'      => strtolower($data['email'] ?? ''),
            'name'       => $data['name'] ?? '',
            'avatar_url' => $data['picture']['data']['url'] ?? null,
        ];
    }

    /**
     * Extract the raw Bearer token from the Authorization header.
     *
     * @return string|null
     */
    private function extractBearerToken(): ?string
    {
        $header = $this->request->getHeaderLine('Authorization');
        if (preg_match('/^Bearer\s+(\S+)$/i', $header, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Validate the request token and return the customer, or null.
     *
     * @return object|null
     */
    private function getAuthenticatedCustomer(): ?object
    {
        $plain = $this->extractBearerToken();
        if (!$plain) {
            return null;
        }

        $customerId = $this->tokens->validate($plain);
        if (!$customerId) {
            return null;
        }

        $customer = $this->customers->find($customerId);

        if (!$customer || $customer->status !== 'active') {
            return null;
        }

        return $customer;
    }

    /**
     * Return a safe, minimal customer object for API responses.
     * Strips internal ids (google_id, facebook_id, wp_user_id).
     *
     * @param object $customer
     * @return array
     */
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
            'status'          => $customer->status,
            'last_login_at'   => $customer->last_login_at,
            'created_at'      => $customer->created_at,
        ];
    }
}

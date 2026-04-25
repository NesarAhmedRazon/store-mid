<?php

namespace App\Controllers\Api;

use App\Models\CustomerModel;
use App\Models\CustomerTokenModel;
use CodeIgniter\RESTful\ResourceController;

/**
 * CustomersController
 *
 * Handles two concerns:
 *   1. WP webhook receiver  — POST /posts/customers
 *   2. Admin dashboard CRUD — GET/POST/PUT/DELETE /admin/customers
 *
 * All responses are JSON.
 */
class CustomersController extends ResourceController
{
    protected string $format = 'json';

    private CustomerModel      $customers;
    private CustomerTokenModel $tokens;

    public function __construct()
    {
        $this->customers = new CustomerModel();
        $this->tokens    = new CustomerTokenModel();
    }

    // ═══════════════════════════════════════════════════════════════════
    // WP Webhook
    // POST /posts/customers
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Receive a customer create/update push from WooCommerce.
     *
     * Expected payload (array of customers or single object):
     * {
     *   "customers": [
     *     {
     *       "wp_user_id": 42,
     *       "email": "user@example.com",
     *       "name": "John Doe",
     *       "phone": "...",
     *       "avatar_url": "...",
     *       "billing_address": { "line1": "...", "city": "...", ... },
     *       "google_id": "...",      // optional
     *       "facebook_id": "..."     // optional
     *     }
     *   ]
     * }
     */
    public function receive()
    {
        // Verify shared secret from WP plugin.
        $secret = $this->request->getHeaderLine('X-WP-Webhook-Secret');
        if ($secret !== env('WP_WEBHOOK_SECRET')) {
            return $this->failUnauthorized('Invalid webhook secret.');
        }

        $body = $this->request->getJSON(true);

        // Accept both a single object and a "customers" array for flexibility.
        $items = $body['customers'] ?? (isset($body['wp_user_id']) ? [$body] : []);

        if (empty($items)) {
            return $this->fail('No customer data found in payload.', 422);
        }

        $results = ['synced' => 0, 'errors' => []];

        foreach ($items as $item) {
            if (empty($item['email'])) {
                $results['errors'][] = ['wp_user_id' => $item['wp_user_id'] ?? null, 'reason' => 'Missing email'];
                continue;
            }

            try {
                $this->customers->upsertFromWp($item);
                $results['synced']++;
            } catch (\Throwable $e) {
                log_message('error', '[CustomerSync] ' . $e->getMessage(), $item);
                $results['errors'][] = [
                    'wp_user_id' => $item['wp_user_id'] ?? null,
                    'reason'     => 'Internal error',
                ];
            }
        }

        return $this->respond($results, 200);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Admin CRUD
    // All routes under /admin/customers  (protect with admin middleware)
    // ═══════════════════════════════════════════════════════════════════

    /**
     * GET /admin/customers
     *
     * Query params: page, per_page, search, status
     */
    public function index()
    {
        $page    = (int) ($this->request->getGet('page')     ?? 1);
        $perPage = (int) ($this->request->getGet('per_page') ?? 25);
        $search  = $this->request->getGet('search')  ?: null;
        $status  = $this->request->getGet('status')  ?: null;

        $perPage = min(max($perPage, 1), 100); // clamp 1-100

        return $this->respond($this->customers->paginated($page, $perPage, $search, $status));
    }

    /**
     * GET /admin/customers/:id
     */
    public function show($id = null)
    {
        $customer = $this->customers->find($id);
        if (!$customer) {
            return $this->failNotFound("Customer #{$id} not found.");
        }

        return $this->respond($customer);
    }

    /**
     * POST /admin/customers
     *
     * Body: { email, name, phone?, avatar_url?, billing_address?, status? }
     */
    public function create()
    {
        $data = $this->request->getJSON(true);

        if (empty($data['email']) || empty($data['name'])) {
            return $this->fail('email and name are required.', 422);
        }

        if ($this->customers->findByEmail($data['email'])) {
            return $this->fail('A customer with this email already exists.', 409);
        }

        $data['source'] = 'manual';
        $data['status'] = $data['status'] ?? 'active';

        if (!empty($data['billing_address'])) {
            $data['billing_address'] = json_encode($data['billing_address']);
        }

        $id = $this->customers->insert($data, true);

        if (!$id) {
            return $this->fail($this->customers->errors(), 422);
        }

        return $this->respondCreated($this->customers->find($id));
    }

    /**
     * PUT /admin/customers/:id
     *
     * Partial updates — only supplied fields are changed.
     */
    public function update($id = null)
    {
        $customer = $this->customers->find($id);
        if (!$customer) {
            return $this->failNotFound("Customer #{$id} not found.");
        }

        $data = $this->request->getJSON(true);

        // Prevent email collision with another customer.
        if (!empty($data['email'])) {
            $byEmail = $this->customers->findByEmail($data['email']);
            if ($byEmail && (int) $byEmail->id !== (int) $id) {
                return $this->fail('Email is already used by another customer.', 409);
            }
        }

        if (!empty($data['billing_address']) && is_array($data['billing_address'])) {
            $data['billing_address'] = json_encode($data['billing_address']);
        }

        if (!$this->customers->update($id, $data)) {
            return $this->fail($this->customers->errors(), 422);
        }

        return $this->respond($this->customers->find($id));
    }

    /**
     * DELETE /admin/customers/:id
     *
     * Also revokes all active tokens for that customer.
     */
    public function delete($id = null)
    {
        $customer = $this->customers->find($id);
        if (!$customer) {
            return $this->failNotFound("Customer #{$id} not found.");
        }

        $this->tokens->revokeAll((int) $id);
        $this->customers->delete($id);

        return $this->respondDeleted(['id' => (int) $id]);
    }
}

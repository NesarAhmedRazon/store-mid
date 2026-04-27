<?php

namespace App\Controllers\Customer;

use App\Models\CustomerModel;
use App\Models\CustomerTokenModel;
use CodeIgniter\RESTful\ResourceController;

/**
 * CustomerController
 *
 * Handles all customer concerns:
 *   1. WP webhook receiver  — POST /api/posts/customers
 *   2. Admin dashboard CRUD — GET/POST /customers/*
 */
class CustomerController extends ResourceController
{
    private CustomerModel      $customers;
    private CustomerTokenModel $tokens;

    public function __construct()
    {
        $this->customers = new CustomerModel();
        $this->tokens    = new CustomerTokenModel();
    }

    // ═══════════════════════════════════════════════════════════════════
    // WP Webhook — POST /api/posts/customers
    // ═══════════════════════════════════════════════════════════════════

    public function receive()
    {
       // ── Auth ────────────────────────────────────────────────────────
        $secret = $this->request->getHeaderLine('X-WC-Webhook-Secret');

        if ($secret !== env('WC_WEBHOOK_SECRET')) {
            log_message('error', 'customers:Invalid webhook secret provided');
            return $this->failUnauthorized('Invalid webhook secret');
        }

        $body = $this->request->getJSON(true);
        
        // Accept both a single object and a "customers" array.
        $items = $body['customers'] ?? (isset($body['wp_user_id']) ? [$body] : []);

        if (empty($items)) {
            return $this->fail('No customer data found in payload.', 422);
        }

        $results = ['synced' => 0, 'errors' => []];

        foreach ($items as $item) {
            
            if (empty($item['email'])) {
                log_message('info',print_r($item,true));
                $results['errors'][] = [
                    'wp_user_id' => $item['wp_user_id'] ?? null,
                    'reason'     => 'Missing email',
                ];
                continue;
            }

            try {
                log_message('info', print_r($item,true));
                $this->customers->upsertFromWp($item);
                $results['synced']++;
            } catch (\Throwable $e) {
                log_message('error', '[CustomerSync] ' . $e->getMessage());
                $results['errors'][] = [
                    'wp_user_id' => $item['wp_user_id'] ?? null,
                    'reason'     => 'Internal error',
                ];
            }
            
        }

        return $this->respond($results, 200);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Index — GET /customers
    // ═══════════════════════════════════════════════════════════════════

    public function index()
    {
        // FIX: removed the blocking `return 'hi'`
        $page    = (int) ($this->request->getGet('page')   ?? 1);
        $perPage = 25;
        $search  = $this->request->getGet('search') ?: null;
        $status  = $this->request->getGet('status') ?: null;

        $result = $this->customers->paginated($page, $perPage, $search, $status);

        return view('admin/customers/index', [
            'title'       => 'Customers',
            'customers'   => $result['data'],
            'total'       => $result['total'],
            'pages'       => $result['pages'],
            'currentPage' => $page,
            'search'      => $search,
            'status'      => $status,
            'perPage'     => $perPage,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Show — GET /customers/:id
    // ═══════════════════════════════════════════════════════════════════

    public function show($id = null)
    {
        $customer = $this->customers->find($id);
        if (!$customer) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Customer #{$id} not found.");
        }

        return view('admin/customers/show', [
            'title'    => $customer->name,
            'customer' => $customer,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════
    // New — GET /customers/new
    // ═══════════════════════════════════════════════════════════════════

    public function new()
    {
        return view('admin/customers/create', [
            'title'  => 'New customer',
            'errors' => session()->getFlashdata('errors') ?? [],
            'old'    => session()->getFlashdata('old')    ?? [],
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Create — POST /customers
    // ═══════════════════════════════════════════════════════════════════

    public function create()
    {
        $data = $this->request->getPost();

        if (empty($data['email']) || empty($data['name'])) {
            session()->setFlashdata('errors', ['Email and name are required.']);
            session()->setFlashdata('old', $data);
            return redirect()->to('/customers/new');
        }

        if ($this->customers->findByEmail($data['email'])) {
            session()->setFlashdata('errors', ['A customer with this email already exists.']);
            session()->setFlashdata('old', $data);
            return redirect()->to('/customers/new');
        }

        $payload = [
            'email'  => strtolower(trim($data['email'])),
            'name'   => trim($data['name']),
            'phone'  => $data['phone']  ?? null,
            'status' => $data['status'] ?? 'active',
            'source' => 'manual',
        ];

        if (!empty($data['billing_address'])) {
            $payload['billing_address'] = json_encode($data['billing_address']);
        }

        $id = $this->customers->insert($payload, true);

        if (!$id) {
            session()->setFlashdata('errors', $this->customers->errors());
            session()->setFlashdata('old', $data);
            return redirect()->to('/customers/new');
        }

        session()->setFlashdata('success', 'Customer created successfully.');
        return redirect()->to("/customers/{$id}");
    }

    // ═══════════════════════════════════════════════════════════════════
    // Edit — GET /customers/:id/edit
    // ═══════════════════════════════════════════════════════════════════

    public function edit($id = null)
    {
        $customer = $this->customers->find($id);
        if (!$customer) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Customer #{$id} not found.");
        }

        return view('admin/customers/edit', [
            'title'    => 'Edit — ' . $customer->name,
            'customer' => $customer,
            'errors'   => session()->getFlashdata('errors') ?? [],
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Update — POST /customers/:id
    // ═══════════════════════════════════════════════════════════════════

    public function update($id = null)
    {
        $customer = $this->customers->find($id);
        if (!$customer) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Customer #{$id} not found.");
        }

        $data = $this->request->getPost();

        if (!empty($data['email'])) {
            $byEmail = $this->customers->findByEmail($data['email']);
            if ($byEmail && (int) $byEmail->id !== (int) $id) {
                session()->setFlashdata('errors', ['This email is already used by another customer.']);
                return redirect()->to("/customers/{$id}/edit");
            }
        }

        $payload = [
            'name'   => trim($data['name']  ?? $customer->name),
            'email'  => strtolower(trim($data['email'] ?? $customer->email)),
            'phone'  => $data['phone']  ?? null,
            'status' => $data['status'] ?? $customer->status,
        ];

        if (!empty($data['billing_address']) && is_array($data['billing_address'])) {
            $payload['billing_address'] = json_encode($data['billing_address']);
        }

        if (!$this->customers->update($id, $payload)) {
            session()->setFlashdata('errors', $this->customers->errors());
            return redirect()->to("/customers/{$id}/edit");
        }

        // If status changed to banned — revoke all active tokens immediately
        if (($payload['status'] ?? null) === 'banned' && $customer->status !== 'banned') {
            $this->tokens->revokeAll((int) $id);
        }

        session()->setFlashdata('success', 'Customer updated successfully.');
        return redirect()->to("/customers/{$id}");
    }

    // ═══════════════════════════════════════════════════════════════════
    // Delete — GET /customers/:id/delete
    // ═══════════════════════════════════════════════════════════════════

    public function delete($id = null)
    {
        $customer = $this->customers->find($id);
        if (!$customer) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Customer #{$id} not found.");
        }

        $this->tokens->revokeAll((int) $id);
        $this->customers->delete($id);

        session()->setFlashdata('success', 'Customer deleted.');
        return redirect()->to('/customers');
    }
}

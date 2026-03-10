<?php

namespace App\Libraries;

use Config\Services;

class ExternalWebhook
{
    protected string $endpoint;
    protected ?string $signature;

    public function __construct()
    {
        $this->endpoint = rtrim(env('EXTERNAL_ENDPOINT', 'https://example.com/webhook'), '/');
        $this->signature = env('EXTERNAL_SIGNATURE') ?: null;
    }

    /**
     * Forward shipment data to external API (non-blocking)
     *
     * @param array $payload Normalized payload:
     * [
     *   'order_id' => '21902',
     *   'status' => 'delivered',
     *   'courier' => 'pathao',
     *   'consignment' => 'DS080326A3QATL',
     *   'updated_at' => '2026-03-10 09:20:28'
     * ]
     * @param string|null $signature Optional signature header
     */
    public function send(array $payload): void
    {
        try {
            $client = Services::curlrequest([
                'timeout' => 2,        // short timeout
                'http_errors' => false, // ignore 4xx/5xx
                'connect_timeout' => 1,
            ]);

            $headers = [
                'x-smdp-signature' => $this->signature ?? 'no-signature',
                'User-Agent' => 'SMDP User Agent/1.2',
                'Content-Type' => 'application/json'
            ];

            

            // Fire-and-forget using async curl options
            $client->post($this->endpoint, [
                'json' => $payload,
                'headers' => $headers
            ])->then(
                function ($response) {
                    log_message('debug', 'External webhook sent: ' . $response->getStatusCode());
                },
                function ($error) {
                    log_message('error', 'Failed sending external webhook: ' . $error->getMessage());
                }
            );
        } catch (\Throwable $e) {
            log_message('error', 'ExternalWebhook error: ' . $e->getMessage());
        }
    }
}
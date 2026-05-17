<?php

namespace Opencart\Catalog\Controller\Extension\NakoPay\Payment;

/**
 * NakoPay payment controller for OpenCart 4 catalog.
 * Handles checkout confirmation (redirect to NakoPay) and webhook callbacks.
 */
class NakoPay extends \Opencart\System\Engine\Controller
{
    private const PRIMARY_BASE = 'https://daslrxpkbkqrbnjwouiq.supabase.co/functions/v1';
    private const FALLBACK_BASE = 'https://api.nakopay.com/v1';
    private const API_VERSION = '2025-04-20';
    private const PLUGIN_VERSION = '1.0.0';

    /**
     * Renders the payment confirmation button at checkout.
     */
    public function index(): string
    {
        $this->load->language('extension/nakopay/payment/nakopay');

        $data['description'] = $this->config->get('payment_nakopay_description') ?: '';
        $data['confirm_url'] = $this->url->link('extension/nakopay/payment/nakopay.confirm', '', true);

        return $this->load->view('extension/nakopay/payment/nakopay', $data);
    }

    /**
     * Called when customer confirms the order - creates NakoPay invoice and redirects.
     */
    public function confirm(): void
    {
        $this->load->model('checkout/order');

        $orderId = $this->session->data['order_id'] ?? 0;
        if (!$orderId) {
            $this->response->redirect($this->url->link('checkout/checkout', '', true));
            return;
        }

        $order = $this->model_checkout_order->getOrder($orderId);
        if (!$order) {
            $this->response->redirect($this->url->link('checkout/checkout', '', true));
            return;
        }

        try {
            $invoice = $this->apiPost('/invoices-create', [
                'amount' => number_format((float) $order['total'], 2, '.', ''),
                'currency' => $order['currency_code'],
                'coin' => $this->config->get('payment_nakopay_coin') ?: 'BTC',
                'description' => sprintf('Order #%s', $order['order_id']),
                'customer_email' => $order['email'],
                'metadata' => [
                    'opencart_order_id' => (string) $order['order_id'],
                    'opencart_store_id' => (string) $order['store_id'],
                ],
                'redirect_url' => $this->url->link('extension/nakopay/payment/nakopay.callback', '', true),
            ]);

            // Set order to pending payment
            $this->model_checkout_order->addHistory(
                $orderId,
                1, // Pending
                sprintf('NakoPay invoice created: %s', $invoice['id'] ?? 'unknown'),
                false,
            );

            $checkoutUrl = $invoice['checkout_url'] ?? null;
            if (!$checkoutUrl) {
                throw new \RuntimeException('No checkout_url in API response');
            }

            $json['redirect'] = $checkoutUrl;
        } catch (\Throwable $e) {
            $json['error'] = 'Unable to create crypto payment. Please try again.';
            error_log('NakoPay confirm error: ' . $e->getMessage());
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Customer returns here after paying.
     */
    public function callback(): void
    {
        $this->response->redirect($this->url->link('checkout/success', '', true));
    }

    /**
     * Webhook endpoint for NakoPay invoice status changes.
     * Verifies HMAC-SHA256 signature before processing.
     *
     * URL: index.php?route=extension/nakopay/payment/nakopay.webhook
     */
    public function webhook(): void
    {
        $rawBody = file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_X_NAKOPAY_SIGNATURE'] ?? '';

        // Verify signature
        $secret = $this->config->get('payment_nakopay_webhook_secret') ?: '';
        if ($secret === '' || $sigHeader === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Missing signature or secret']);
            return;
        }

        try {
            $event = $this->verifySignature($rawBody, $sigHeader, $secret);
        } catch (\RuntimeException $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }

        $eventType = $event['type'] ?? '';
        $invoiceData = $event['data']['object'] ?? [];
        $metadata = $invoiceData['metadata'] ?? [];
        $orderId = (int) ($metadata['opencart_order_id'] ?? 0);

        if ($orderId <= 0) {
            echo json_encode(['received' => true]);
            return;
        }

        $this->load->model('checkout/order');

        switch ($eventType) {
            case 'invoice.paid':
                $statusId = (int) ($this->config->get('payment_nakopay_order_status_id') ?: 5); // Complete
                $coin = $invoiceData['coin'] ?? '';
                $amountCrypto = $invoiceData['amount_crypto'] ?? '';
                $txId = $invoiceData['tx_id'] ?? '';
                $comment = sprintf('NakoPay payment confirmed. %s %s (tx: %s)', $amountCrypto, $coin, $txId ?: 'n/a');
                $this->model_checkout_order->addHistory($orderId, $statusId, $comment, false);
                break;

            case 'invoice.expired':
                $this->model_checkout_order->addHistory($orderId, 14, 'NakoPay invoice expired.', false); // Expired
                break;

            case 'invoice.canceled':
                $this->model_checkout_order->addHistory($orderId, 7, 'NakoPay invoice canceled.', false); // Canceled
                break;
        }

        echo json_encode(['received' => true]);
    }

    /**
     * Verify HMAC-SHA256 webhook signature.
     *
     * @return array<string, mixed> Parsed event
     */
    private function verifySignature(string $payload, string $sigHeader, string $secret, int $tolerance = 300): array
    {
        $parts = [];
        foreach (explode(',', $sigHeader) as $kv) {
            $i = strpos($kv, '=');
            if ($i === false) continue;
            $parts[trim(substr($kv, 0, $i))] = trim(substr($kv, $i + 1));
        }

        if (!isset($parts['t'], $parts['v1']) || !ctype_digit($parts['t'])) {
            throw new \RuntimeException('Malformed signature header');
        }

        $t = (int) $parts['t'];
        if (abs(time() - $t) > $tolerance) {
            throw new \RuntimeException("Timestamp {$t} outside tolerance of {$tolerance}s");
        }

        $expected = hash_hmac('sha256', "{$t}.{$payload}", $secret);
        if (!hash_equals($expected, $parts['v1'])) {
            throw new \RuntimeException('Signature mismatch');
        }

        return json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
    }

    private function getBaseUrl(): string
    {
        $adminUrl = trim((string) $this->config->get('payment_nakopay_api_base_url'));
        if ($adminUrl !== '') {
            return rtrim($adminUrl, '/');
        }
        if (defined('NAKOPAY_API_BASE')) {
            return rtrim((string) constant('NAKOPAY_API_BASE'), '/');
        }
        return self::PRIMARY_BASE;
    }

    /**
     * @return array<string, mixed>
     */
    private function apiPost(string $path, array $body): array
    {
        $url = $this->getBaseUrl() . $path;
        $json = json_encode($body, JSON_THROW_ON_ERROR);
        $apiKey = $this->config->get('payment_nakopay_api_key') ?: '';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
                'X-NakoPay-Version: ' . self::API_VERSION,
                'Idempotency-Key: idem_' . bin2hex(random_bytes(16)),
                'User-Agent: nakopay-opencart/' . self::PLUGIN_VERSION,
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException("NakoPay API connection failed: {$err}");
        }

        $data = json_decode((string) $response, true);
        if ($httpCode < 200 || $httpCode >= 300) {
            $msg = $data['error']['message'] ?? "HTTP {$httpCode}";
            throw new \RuntimeException("NakoPay API error: {$msg}");
        }

        return $data ?? [];
    }
}

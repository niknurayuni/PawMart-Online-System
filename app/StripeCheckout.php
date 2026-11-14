<?php
declare(strict_types=1);

final class StripeCheckout
{
    public function __construct(private string $key, private string $appUrl) {}

    public function assertConfigured(): void
    {
        if (!preg_match('/^(sk|rk)_test_[A-Za-z0-9]+$/D', $this->key) || !function_exists('curl_init')) {
            throw new RuntimeException('Stripe test checkout requires a test secret key and the PHP cURL extension.');
        }
    }

    public function create(int $orderId, int $amount): array
    {
        return $this->request('', [
            'mode' => 'payment',
            'allowed_payment_method_types' => ['card'],
            'adaptive_pricing' => ['enabled' => 'false'],
            'client_reference_id' => (string)$orderId,
            'success_url' => $this->appUrl . '?page=payment-return&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->appUrl . '?page=orders',
            'expires_at' => time() + 31 * 60,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'myr',
                    'unit_amount' => $amount,
                    'product_data' => ['name' => 'PawMart order #' . $orderId],
                ],
            ]],
        ]);
    }

    public function retrieve(string $id): array
    {
        return $this->request($this->sessionPath($id));
    }

    public function expire(string $id): array
    {
        return $this->request($this->sessionPath($id) . '/expire', []);
    }

    private function sessionPath(string $id): string
    {
        if (!preg_match('/^cs_test_[A-Za-z0-9]+$/D', $id)) {
            throw new InvalidArgumentException('Invalid Stripe test checkout reference.');
        }
        return '/' . rawurlencode($id);
    }

    public function url(array $session): string
    {
        $url = $session['url'] ?? '';
        if (!is_string($url) || !str_starts_with($url, 'https://checkout.stripe.com/')) {
            throw new RuntimeException('Stripe did not return an available checkout link.');
        }
        return $url;
    }

    private function request(string $path, ?array $data = null): array
    {
        $this->assertConfigured();
        $curl = curl_init('https://api.stripe.com/v1/checkout/sessions' . $path);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $this->key . ':',
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 12,
        ]);
        if ($data !== null) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        $body = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $curlCode = curl_errno($curl);
        curl_close($curl);
        $session = is_string($body) ? json_decode($body, true) : null;
        if ($status !== 200 || !is_array($session) || ($session['livemode'] ?? null) !== false) {
            $code = $session['error']['code'] ?? '';
            $parameter = $session['error']['param'] ?? '';
            $code = is_string($code) && preg_match('/^[a-z_]{1,80}$/D', $code) ? $code : '';
            $parameter = is_string($parameter) && preg_match('/^[a-z_][a-z_0-9\[\]]{0,79}$/D', $parameter) ? $parameter : '';
            error_log("PawMart Stripe Checkout HTTP $status, cURL $curlCode, code $code, parameter $parameter");

            $message = match (true) {
                $curlCode === 60 || $curlCode === 77 => 'PHP could not verify the Stripe TLS certificate. Check the XAMPP PHP CA bundle.',
                $curlCode !== 0 => "PHP could not connect to Stripe (cURL error $curlCode). Check the PHP terminal for the request log.",
                $status === 401 => 'Stripe rejected the test secret key (HTTP 401). Check the key and restart the PHP server.',
                $status === 403 => 'The Stripe test key cannot access Checkout Sessions (HTTP 403). Check its permissions.',
                $status === 400 && $parameter !== '' => "Stripe rejected the checkout parameter $parameter (HTTP 400).",
                default => "Stripe checkout is unavailable (HTTP $status). Check the PHP terminal for the request log.",
            };
            throw new RuntimeException($message);
        }
        return $session;
    }

    public static function verifyEvent(string $body, string $signature, string $secret): array
    {
        $timestamp = '';
        $signatures = [];
        foreach (explode(',', $signature) as $part) {
            [$name, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            if ($name === 't') $timestamp = $value;
            if ($name === 'v1') $signatures[] = $value;
        }
        if ($secret === '' || !ctype_digit($timestamp) || abs(time() - (int)$timestamp) > 300) {
            throw new InvalidArgumentException('Invalid Stripe webhook timestamp or configuration.');
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $body, $secret);
        foreach ($signatures as $candidate) {
            if (hash_equals($expected, $candidate)) {
                $event = json_decode($body, true);
                if (is_array($event) && ($event['livemode'] ?? null) === false) return $event;
                break;
            }
        }
        throw new InvalidArgumentException('Invalid Stripe test webhook.');
    }
}

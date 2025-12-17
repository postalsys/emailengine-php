<?php

declare(strict_types=1);

namespace Postalsys\EmailEnginePhp;

use Postalsys\EmailEnginePhp\Exceptions\EmailEngineException;
use Postalsys\EmailEnginePhp\Resources\Accounts;
use Postalsys\EmailEnginePhp\Resources\Blocklists;
use Postalsys\EmailEnginePhp\Resources\Gateways;
use Postalsys\EmailEnginePhp\Resources\Mailboxes;
use Postalsys\EmailEnginePhp\Resources\Messages;
use Postalsys\EmailEnginePhp\Resources\OAuth2;
use Postalsys\EmailEnginePhp\Resources\Outbox;
use Postalsys\EmailEnginePhp\Resources\Settings;
use Postalsys\EmailEnginePhp\Resources\Stats;
use Postalsys\EmailEnginePhp\Resources\Templates;
use Postalsys\EmailEnginePhp\Resources\Tokens;
use Postalsys\EmailEnginePhp\Resources\Webhooks;

/**
 * EmailEngine PHP SDK
 *
 * A modern PHP client for the EmailEngine API.
 *
 * @property-read Accounts $accounts Account management
 * @property-read Messages $messages Message operations
 * @property-read Mailboxes $mailboxes Mailbox management
 * @property-read Outbox $outbox Outbox/queue management
 * @property-read Settings $settings System settings
 * @property-read Tokens $tokens Access token management
 * @property-read Templates $templates Email templates
 * @property-read Gateways $gateways SMTP gateway management
 * @property-read OAuth2 $oauth2 OAuth2 application management
 * @property-read Webhooks $webhooks Webhook route management
 * @property-read Stats $stats System statistics
 * @property-read Blocklists $blocklists Blocklist management
 */
class EmailEngine
{
    private HttpClient $httpClient;
    private ?Accounts $accounts = null;
    private ?Messages $messages = null;
    private ?Mailboxes $mailboxes = null;
    private ?Outbox $outbox = null;
    private ?Settings $settings = null;
    private ?Tokens $tokens = null;
    private ?Templates $templates = null;
    private ?Gateways $gateways = null;
    private ?OAuth2 $oauth2 = null;
    private ?Webhooks $webhooks = null;
    private ?Stats $stats = null;
    private ?Blocklists $blocklists = null;

    /**
     * Create a new EmailEngine client instance
     *
     * @param string $accessToken API access token
     * @param string $baseUrl Base URL of EmailEngine (default: http://localhost:3000)
     * @param string|null $serviceSecret Service secret for signing hosted auth URLs
     * @param string|null $redirectUrl Default redirect URL for hosted authentication
     * @param int $timeout Request timeout in seconds (default: 30)
     */
    public function __construct(
        private readonly string $accessToken,
        private readonly string $baseUrl = 'http://localhost:3000',
        private readonly ?string $serviceSecret = null,
        private readonly ?string $redirectUrl = null,
        int $timeout = 30,
    ) {
        $this->httpClient = new HttpClient(
            baseUrl: rtrim($baseUrl, '/'),
            accessToken: $accessToken,
            timeout: $timeout,
        );
    }

    /**
     * Create an EmailEngine client from an options array (legacy compatibility)
     *
     * @param array{
     *     access_token: string,
     *     ee_base_url?: string,
     *     service_secret?: string,
     *     redirect_url?: string,
     *     timeout?: int
     * } $options Configuration options
     */
    public static function fromOptions(array $options): self
    {
        return new self(
            accessToken: $options['access_token'],
            baseUrl: $options['ee_base_url'] ?? 'http://localhost:3000',
            serviceSecret: $options['service_secret'] ?? null,
            redirectUrl: $options['redirect_url'] ?? null,
            timeout: $options['timeout'] ?? 30,
        );
    }

    /**
     * Magic getter for resource instances
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'accounts' => $this->accounts(),
            'messages' => $this->messages(),
            'mailboxes' => $this->mailboxes(),
            'outbox' => $this->outbox(),
            'settings' => $this->settings(),
            'tokens' => $this->tokens(),
            'templates' => $this->templates(),
            'gateways' => $this->gateways(),
            'oauth2' => $this->oauth2(),
            'webhooks' => $this->webhooks(),
            'stats' => $this->stats(),
            'blocklists' => $this->blocklists(),
            default => throw new \InvalidArgumentException("Unknown property: {$name}"),
        };
    }

    /**
     * Get the Accounts resource
     */
    public function accounts(): Accounts
    {
        return $this->accounts ??= new Accounts($this->httpClient);
    }

    /**
     * Get the Messages resource
     */
    public function messages(): Messages
    {
        return $this->messages ??= new Messages($this->httpClient);
    }

    /**
     * Get the Mailboxes resource
     */
    public function mailboxes(): Mailboxes
    {
        return $this->mailboxes ??= new Mailboxes($this->httpClient);
    }

    /**
     * Get the Outbox resource
     */
    public function outbox(): Outbox
    {
        return $this->outbox ??= new Outbox($this->httpClient);
    }

    /**
     * Get the Settings resource
     */
    public function settings(): Settings
    {
        return $this->settings ??= new Settings($this->httpClient);
    }

    /**
     * Get the Tokens resource
     */
    public function tokens(): Tokens
    {
        return $this->tokens ??= new Tokens($this->httpClient);
    }

    /**
     * Get the Templates resource
     */
    public function templates(): Templates
    {
        return $this->templates ??= new Templates($this->httpClient);
    }

    /**
     * Get the Gateways resource
     */
    public function gateways(): Gateways
    {
        return $this->gateways ??= new Gateways($this->httpClient);
    }

    /**
     * Get the OAuth2 resource
     */
    public function oauth2(): OAuth2
    {
        return $this->oauth2 ??= new OAuth2($this->httpClient);
    }

    /**
     * Get the Webhooks resource
     */
    public function webhooks(): Webhooks
    {
        return $this->webhooks ??= new Webhooks($this->httpClient);
    }

    /**
     * Get the Stats resource
     */
    public function stats(): Stats
    {
        return $this->stats ??= new Stats($this->httpClient);
    }

    /**
     * Get the Blocklists resource
     */
    public function blocklists(): Blocklists
    {
        return $this->blocklists ??= new Blocklists($this->httpClient);
    }

    /**
     * Generate a redirect URL for EmailEngine's hosted authentication page
     *
     * @param array{
     *     account?: string|null,
     *     name?: string,
     *     email?: string,
     *     redirectUrl?: string
     * } $data Authentication data
     * @throws EmailEngineException If service secret is not configured
     */
    public function getAuthenticationUrl(array $data = []): string
    {
        if ($this->serviceSecret === null) {
            throw new EmailEngineException(
                message: 'Service secret is required for generating authentication URLs'
            );
        }

        // Use provided redirectUrl or fall back to default
        if (empty($data['redirectUrl']) && $this->redirectUrl !== null) {
            $data['redirectUrl'] = $this->redirectUrl;
        }

        $dataJson = json_encode($data, JSON_THROW_ON_ERROR);
        $signature = $this->signRequest($dataJson, $this->serviceSecret);

        return $this->baseUrl . '/accounts/new?data=' .
            $this->base64EncodeUrlsafe($dataJson) .
            '&sig=' .
            $this->base64EncodeUrlsafe($signature);
    }

    /**
     * Make a raw API request (for endpoints not covered by resources)
     *
     * @param array<string, mixed>|null $data Request body for POST/PUT
     * @param array<string, mixed> $query Query parameters
     * @param array<string, string> $headers Additional headers
     * @return array<string, mixed>
     * @throws EmailEngineException
     */
    public function request(
        string $method,
        string $path,
        ?array $data = null,
        array $query = [],
        array $headers = [],
    ): array {
        return $this->httpClient->request($method, $path, $data, $query, $headers);
    }

    /**
     * Get the underlying HTTP client
     */
    public function getHttpClient(): HttpClient
    {
        return $this->httpClient;
    }

    /**
     * Encode value to a URL-safe base64 string
     */
    public function base64EncodeUrlsafe(string $value): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($value));
    }

    /**
     * Decode a URL-safe base64 encoded value
     */
    public function base64DecodeUrlsafe(string $value): string
    {
        $data = str_replace(['-', '_'], ['+', '/'], $value);
        $mod4 = strlen($data) % 4;

        if ($mod4) {
            $data .= substr('====', $mod4);
        }

        return base64_decode($data, true) ?: '';
    }

    /**
     * Sign a value with HMAC-SHA256
     */
    public function signRequest(string $value, string $secret): string
    {
        return hash_hmac('sha256', $value, $secret, true);
    }

    // =========================================================================
    // Legacy method aliases for backward compatibility
    // =========================================================================

    /**
     * @deprecated Use getAuthenticationUrl() instead
     */
    public function get_authentication_url(array $data): string
    {
        return $this->getAuthenticationUrl($data);
    }

    /**
     * @deprecated Use settings()->setWebhooks() instead
     */
    public function set_webhook_settings(array $opts): bool
    {
        return $this->settings()->setWebhooks($opts);
    }

    /**
     * @deprecated Use settings()->getWebhooks() instead
     * @return array{enabled: bool, url: string, events: array, headers: array, text: int|false}
     */
    public function get_webhook_settings(): array
    {
        return $this->settings()->getWebhooks();
    }

    /**
     * @deprecated Use base64EncodeUrlsafe() instead
     */
    public function base64_encode_urlsafe(string $val): string
    {
        return $this->base64EncodeUrlsafe($val);
    }

    /**
     * @deprecated Use base64DecodeUrlsafe() instead
     */
    public function base64_decode_urlsafe(string $val): string
    {
        return $this->base64DecodeUrlsafe($val);
    }

    /**
     * @deprecated Use signRequest() instead
     */
    public function sign_request(string $val, string $service_secret): string
    {
        return $this->signRequest($val, $service_secret);
    }

    /**
     * Download and stream a file (e.g., attachment) directly to the browser
     *
     * @param string $path The API path to download from (e.g., /v1/account/{account}/attachment/{attachment})
     * @throws EmailEngineException
     */
    public function download(string $path): void
    {
        $client = $this->httpClient->getGuzzleClient();

        $response = $client->request('GET', $path, [
            'stream' => true,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new EmailEngineException(
                message: 'Invalid HTTP response ' . $response->getStatusCode(),
                statusCode: $response->getStatusCode()
            );
        }

        $contentType = $response->getHeader('Content-Type');
        if (!empty($contentType[0])) {
            header("Content-Type: {$contentType[0]}");
        }

        $contentDisposition = $response->getHeader('Content-Disposition');
        if (!empty($contentDisposition[0])) {
            header("Content-Disposition: {$contentDisposition[0]}");
        }

        $body = $response->getBody();
        while (!$body->eof()) {
            echo $body->read(1024);
        }
    }
}

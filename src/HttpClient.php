<?php

declare(strict_types=1);

namespace Postalsys\EmailEnginePhp;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Postalsys\EmailEnginePhp\Exceptions\AuthenticationException;
use Postalsys\EmailEnginePhp\Exceptions\AuthorizationException;
use Postalsys\EmailEnginePhp\Exceptions\EmailEngineException;
use Postalsys\EmailEnginePhp\Exceptions\NotFoundException;
use Postalsys\EmailEnginePhp\Exceptions\RateLimitException;
use Postalsys\EmailEnginePhp\Exceptions\ServerException;
use Postalsys\EmailEnginePhp\Exceptions\ValidationException;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP client for making requests to the EmailEngine API
 */
class HttpClient
{
    private Client $client;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $accessToken,
        private readonly ?int $timeout = 30,
    ) {
        $this->client = new Client([
            'base_uri' => rtrim($baseUrl, '/'),
            'timeout' => $timeout,
        ]);
    }

    /**
     * Make a GET request
     *
     * @param array<string, mixed> $query Query parameters
     * @param array<string, string> $headers Additional headers
     * @return array<string, mixed>
     * @throws EmailEngineException
     */
    public function get(string $path, array $query = [], array $headers = []): array
    {
        return $this->request('GET', $path, query: $query, headers: $headers);
    }

    /**
     * Make a POST request
     *
     * @param array<string, mixed>|null $data Request body
     * @param array<string, string> $headers Additional headers
     * @return array<string, mixed>
     * @throws EmailEngineException
     */
    public function post(string $path, ?array $data = null, array $headers = []): array
    {
        return $this->request('POST', $path, data: $data, headers: $headers);
    }

    /**
     * Make a PUT request
     *
     * @param array<string, mixed>|null $data Request body
     * @param array<string, string> $headers Additional headers
     * @return array<string, mixed>
     * @throws EmailEngineException
     */
    public function put(string $path, ?array $data = null, array $headers = []): array
    {
        return $this->request('PUT', $path, data: $data, headers: $headers);
    }

    /**
     * Make a DELETE request
     *
     * @param array<string, mixed> $query Query parameters
     * @param array<string, string> $headers Additional headers
     * @return array<string, mixed>
     * @throws EmailEngineException
     */
    public function delete(string $path, array $query = [], array $headers = []): array
    {
        return $this->request('DELETE', $path, query: $query, headers: $headers);
    }

    /**
     * Make an HTTP request to the EmailEngine API
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
        $options = [
            'headers' => array_merge([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Accept' => 'application/json',
            ], $headers),
        ];

        if (!empty($query)) {
            $options['query'] = $query;
        }

        if ($data !== null) {
            $options['json'] = $data;
        }

        try {
            $response = $this->client->request($method, $path, $options);
            return $this->parseResponse($response);
        } catch (RequestException $e) {
            throw $this->handleRequestException($e);
        } catch (GuzzleException $e) {
            throw new EmailEngineException(
                message: 'HTTP request failed: ' . $e->getMessage(),
                code: 0,
                previous: $e
            );
        }
    }

    /**
     * Parse the response body as JSON
     *
     * @return array<string, mixed>
     */
    private function parseResponse(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();

        if (empty($body)) {
            return [];
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new EmailEngineException(
                message: 'Failed to parse JSON response: ' . json_last_error_msg(),
                code: 0
            );
        }

        return $data ?? [];
    }

    /**
     * Handle request exceptions and convert to appropriate EmailEngine exceptions
     */
    private function handleRequestException(RequestException $e): EmailEngineException
    {
        $response = $e->getResponse();
        $statusCode = $response?->getStatusCode() ?? 0;
        $errorData = $this->extractErrorData($response);

        $message = $errorData['error'] ?? $e->getMessage();
        $errorCode = $errorData['code'] ?? null;
        $details = $errorData['details'] ?? null;

        return match (true) {
            $statusCode === 400 => new ValidationException($message, $errorCode, $details, $statusCode, $e),
            $statusCode === 401 => new AuthenticationException($message, $errorCode, $details, $statusCode, $e),
            $statusCode === 403 => new AuthorizationException($message, $errorCode, $details, $statusCode, $e),
            $statusCode === 404 => new NotFoundException($message, $errorCode, $details, $statusCode, $e),
            $statusCode === 429 => new RateLimitException(
                $message,
                $errorCode,
                $details,
                $this->extractRetryAfter($response),
                $statusCode
            ),
            $statusCode >= 500 => new ServerException($message, $errorCode, $details, $statusCode, $e),
            default => new EmailEngineException($message, $errorCode, $details, $statusCode, $e),
        };
    }

    /**
     * Extract error data from response
     *
     * @return array<string, mixed>
     */
    private function extractErrorData(?ResponseInterface $response): array
    {
        if ($response === null) {
            return [];
        }

        $body = (string) $response->getBody();

        if (empty($body)) {
            return [];
        }

        $data = json_decode($body, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Extract Retry-After header value
     */
    private function extractRetryAfter(?ResponseInterface $response): ?int
    {
        if ($response === null) {
            return null;
        }

        $retryAfter = $response->getHeaderLine('Retry-After');

        if (empty($retryAfter)) {
            return null;
        }

        return (int) $retryAfter;
    }

    /**
     * Get the underlying Guzzle client
     */
    public function getGuzzleClient(): Client
    {
        return $this->client;
    }

    /**
     * Set a custom Guzzle client (useful for testing)
     */
    public function setGuzzleClient(Client $client): void
    {
        $this->client = $client;
    }
}

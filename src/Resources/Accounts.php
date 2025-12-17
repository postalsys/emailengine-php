<?php

declare(strict_types=1);

namespace Postalsys\EmailEnginePhp\Resources;

use Postalsys\EmailEnginePhp\HttpClient;
use Postalsys\EmailEnginePhp\Exceptions\EmailEngineException;

/**
 * Account management resource
 *
 * Handles all account-related API operations including creating, updating,
 * listing, and deleting email accounts.
 */
class Accounts
{
    public function __construct(
        private readonly HttpClient $client,
    ) {
    }

    /**
     * Create a new email account
     *
     * @param array{
     *     account?: string|null,
     *     name?: string,
     *     email?: string,
     *     imap?: array{
     *         host: string,
     *         port: int,
     *         secure?: bool,
     *         auth: array{user: string, pass: string}
     *     },
     *     smtp?: array{
     *         host: string,
     *         port: int,
     *         secure?: bool,
     *         auth: array{user: string, pass: string}
     *     },
     *     oauth2?: array{
     *         provider: string,
     *         auth: array{user: string, accessToken?: string, refreshToken?: string}
     *     },
     *     path?: string,
     *     webhooks?: string,
     *     proxy?: string,
     *     copy?: bool,
     *     notifyFrom?: string,
     *     syncFrom?: string
     * } $data Account configuration
     * @return array{account: string, state: string}
     * @throws EmailEngineException
     */
    public function create(array $data): array
    {
        return $this->client->post('/v1/account', $data);
    }

    /**
     * Get account information
     *
     * @param string $accountId Account identifier
     * @return array{
     *     account: string,
     *     name?: string,
     *     email?: string,
     *     state: string,
     *     webhooks?: string,
     *     proxy?: string,
     *     path?: string,
     *     imap?: array,
     *     smtp?: array,
     *     oauth2?: array,
     *     counters?: array{messages?: int, total?: int}
     * }
     * @throws EmailEngineException
     */
    public function get(string $accountId): array
    {
        return $this->client->get("/v1/account/{$accountId}");
    }

    /**
     * List all accounts
     *
     * @param array{
     *     page?: int,
     *     pageSize?: int,
     *     state?: string,
     *     query?: string,
     *     counters?: bool
     * } $params Query parameters
     * @return array{
     *     accounts: array,
     *     total?: int,
     *     page?: int,
     *     pages?: int
     * }
     * @throws EmailEngineException
     */
    public function list(array $params = []): array
    {
        return $this->client->get('/v1/accounts', $params);
    }

    /**
     * Update an existing account
     *
     * @param string $accountId Account identifier
     * @param array{
     *     name?: string,
     *     email?: string,
     *     webhooks?: string|null,
     *     proxy?: string|null,
     *     path?: string,
     *     imap?: array,
     *     smtp?: array
     * } $data Fields to update
     * @return array{account: string}
     * @throws EmailEngineException
     */
    public function update(string $accountId, array $data): array
    {
        return $this->client->put("/v1/account/{$accountId}", $data);
    }

    /**
     * Delete an account
     *
     * @param string $accountId Account identifier
     * @return array{account: string, deleted: bool}
     * @throws EmailEngineException
     */
    public function delete(string $accountId): array
    {
        return $this->client->delete("/v1/account/{$accountId}");
    }

    /**
     * Force account reconnection
     *
     * @param string $accountId Account identifier
     * @param array{reconnect?: bool, flush?: bool} $params Optional parameters
     * @return array{account: string}
     * @throws EmailEngineException
     */
    public function reconnect(string $accountId, array $params = []): array
    {
        return $this->client->put("/v1/account/{$accountId}/reconnect", $params);
    }

    /**
     * Trigger account synchronization
     *
     * @param string $accountId Account identifier
     * @return array{account: string}
     * @throws EmailEngineException
     */
    public function sync(string $accountId): array
    {
        return $this->client->put("/v1/account/{$accountId}/sync");
    }

    /**
     * Flush account data/cache
     *
     * @param string $accountId Account identifier
     * @param array{messages?: bool} $params Optional parameters
     * @return array{account: string}
     * @throws EmailEngineException
     */
    public function flush(string $accountId, array $params = []): array
    {
        return $this->client->put("/v1/account/{$accountId}/flush", $params);
    }

    /**
     * Verify account credentials
     *
     * @param array{
     *     imap?: array{
     *         host: string,
     *         port: int,
     *         secure?: bool,
     *         auth: array{user: string, pass: string}
     *     },
     *     smtp?: array{
     *         host: string,
     *         port: int,
     *         secure?: bool,
     *         auth: array{user: string, pass: string}
     *     }
     * } $data Credentials to verify
     * @return array{imap?: array{success: bool}, smtp?: array{success: bool}}
     * @throws EmailEngineException
     */
    public function verify(array $data): array
    {
        return $this->client->post('/v1/verifyAccount', $data);
    }

    /**
     * Get account logs
     *
     * @param string $accountId Account identifier
     * @param array{page?: int, pageSize?: int} $params Query parameters
     * @return array{logs: array, total?: int}
     * @throws EmailEngineException
     */
    public function getLogs(string $accountId, array $params = []): array
    {
        return $this->client->get("/v1/logs/{$accountId}", $params);
    }

    /**
     * Get OAuth2 token information for account
     *
     * @param string $accountId Account identifier
     * @return array{accessToken?: string, expires?: string}
     * @throws EmailEngineException
     */
    public function getOAuthToken(string $accountId): array
    {
        return $this->client->get("/v1/account/{$accountId}/oauth-token");
    }

    /**
     * Get server signatures for account
     *
     * @param string $accountId Account identifier
     * @return array{signatures: array}
     * @throws EmailEngineException
     */
    public function getServerSignatures(string $accountId): array
    {
        return $this->client->get("/v1/account/{$accountId}/server-signatures");
    }

    /**
     * Run a delivery test for the account
     *
     * @param string $accountId Account identifier
     * @param array{to?: string} $data Test configuration
     * @return array{deliveryTest: string}
     * @throws EmailEngineException
     */
    public function createDeliveryTest(string $accountId, array $data = []): array
    {
        return $this->client->post("/v1/delivery-test/account/{$accountId}", $data);
    }

    /**
     * Check delivery test status
     *
     * @param string $deliveryTestId Delivery test identifier
     * @return array{deliveryTest: string, status: string}
     * @throws EmailEngineException
     */
    public function checkDeliveryTest(string $deliveryTestId): array
    {
        return $this->client->get("/v1/delivery-test/check/{$deliveryTestId}");
    }
}

<?php

declare(strict_types=1);

namespace Postalsys\EmailEnginePhp\Resources;

use Postalsys\EmailEnginePhp\HttpClient;
use Postalsys\EmailEnginePhp\Exceptions\EmailEngineException;

/**
 * Access token management resource
 *
 * Handles API access token operations including creating, listing,
 * and deleting tokens.
 */
class Tokens
{
    public function __construct(
        private readonly HttpClient $client,
    ) {
    }

    /**
     * List all access tokens
     *
     * @return array{tokens: array<array{
     *     id: string,
     *     account?: string,
     *     description?: string,
     *     scopes?: array<string>,
     *     created?: string,
     *     lastUse?: array{time?: string, address?: string}
     * }>}
     * @throws EmailEngineException
     */
    public function list(): array
    {
        return $this->client->get('/v1/tokens');
    }

    /**
     * List tokens for a specific account
     *
     * @param string $accountId Account identifier
     * @return array{tokens: array}
     * @throws EmailEngineException
     */
    public function listForAccount(string $accountId): array
    {
        return $this->client->get("/v1/tokens/account/{$accountId}");
    }

    /**
     * Create a new access token
     *
     * @param array{
     *     account: string,
     *     description?: string,
     *     scopes?: array<string>,
     *     ip?: array<string>
     * } $data Token configuration (account is required for API-created tokens)
     * @return array{token: string, id: string}
     * @throws EmailEngineException
     */
    public function create(array $data): array
    {
        return $this->client->post('/v1/token', $data);
    }

    /**
     * Delete an access token
     *
     * @param string $tokenId Token identifier (not the token value)
     * @return array{deleted: bool}
     * @throws EmailEngineException
     */
    public function delete(string $tokenId): array
    {
        return $this->client->delete("/v1/token/{$tokenId}");
    }
}

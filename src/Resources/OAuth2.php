<?php

declare(strict_types=1);

namespace Postalsys\EmailEnginePhp\Resources;

use Postalsys\EmailEnginePhp\HttpClient;
use Postalsys\EmailEnginePhp\Exceptions\EmailEngineException;

/**
 * OAuth2 application management resource
 *
 * Handles OAuth2 app operations including creating, listing,
 * updating, and deleting OAuth2 applications.
 */
class OAuth2
{
    public function __construct(
        private readonly HttpClient $client,
    ) {
    }

    /**
     * List all OAuth2 applications
     *
     * @return array{apps: array<array{
     *     id: string,
     *     provider?: string,
     *     name?: string,
     *     description?: string,
     *     enabled?: bool
     * }>}
     * @throws EmailEngineException
     */
    public function list(): array
    {
        return $this->client->get('/v1/oauth2');
    }

    /**
     * Get OAuth2 application details
     *
     * @param string $appId Application identifier or provider name
     * @return array{
     *     id: string,
     *     provider?: string,
     *     name?: string,
     *     description?: string,
     *     enabled?: bool,
     *     clientId?: string,
     *     redirectUrl?: string,
     *     authority?: string,
     *     scopes?: array<string>
     * }
     * @throws EmailEngineException
     */
    public function get(string $appId): array
    {
        return $this->client->get("/v1/oauth2/{$appId}");
    }

    /**
     * Create a new OAuth2 application
     *
     * @param array{
     *     provider: string,
     *     name?: string,
     *     description?: string,
     *     enabled?: bool,
     *     clientId: string,
     *     clientSecret: string,
     *     redirectUrl?: string,
     *     authority?: string,
     *     scopes?: array<string>,
     *     skipScopes?: bool,
     *     serviceClient?: string,
     *     serviceKey?: string
     * } $data Application configuration
     * @return array{id: string, provider: string}
     * @throws EmailEngineException
     */
    public function create(array $data): array
    {
        return $this->client->post('/v1/oauth2', $data);
    }

    /**
     * Update an OAuth2 application
     *
     * @param string $appId Application identifier
     * @param array{
     *     name?: string,
     *     description?: string,
     *     enabled?: bool,
     *     clientId?: string,
     *     clientSecret?: string,
     *     redirectUrl?: string,
     *     authority?: string,
     *     scopes?: array<string>,
     *     skipScopes?: bool,
     *     serviceClient?: string,
     *     serviceKey?: string
     * } $data Application configuration
     * @return array{id: string, updated: bool}
     * @throws EmailEngineException
     */
    public function update(string $appId, array $data): array
    {
        return $this->client->put("/v1/oauth2/{$appId}", $data);
    }

    /**
     * Delete an OAuth2 application
     *
     * @param string $appId Application identifier
     * @return array{id: string, deleted: bool}
     * @throws EmailEngineException
     */
    public function delete(string $appId): array
    {
        return $this->client->delete("/v1/oauth2/{$appId}");
    }
}

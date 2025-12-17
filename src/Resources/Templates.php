<?php

declare(strict_types=1);

namespace Postalsys\EmailEnginePhp\Resources;

use Postalsys\EmailEnginePhp\HttpClient;
use Postalsys\EmailEnginePhp\Exceptions\EmailEngineException;

/**
 * Email template management resource
 *
 * Handles template operations including creating, listing,
 * updating, and deleting email templates.
 */
class Templates
{
    public function __construct(
        private readonly HttpClient $client,
    ) {
    }

    /**
     * List all templates
     *
     * @param array{page?: int, pageSize?: int} $params Query parameters
     * @return array{
     *     templates: array<array{
     *         id: string,
     *         name?: string,
     *         description?: string,
     *         created?: string,
     *         updated?: string
     *     }>,
     *     total?: int,
     *     page?: int,
     *     pages?: int
     * }
     * @throws EmailEngineException
     */
    public function list(array $params = []): array
    {
        return $this->client->get('/v1/templates', $params);
    }

    /**
     * Get template details
     *
     * @param string $templateId Template identifier
     * @return array{
     *     id: string,
     *     name?: string,
     *     description?: string,
     *     format?: string,
     *     content?: array{subject?: string, text?: string, html?: string},
     *     created?: string,
     *     updated?: string
     * }
     * @throws EmailEngineException
     */
    public function get(string $templateId): array
    {
        return $this->client->get("/v1/templates/{$templateId}");
    }

    /**
     * Create or update a template
     *
     * @param string $templateId Template identifier
     * @param array{
     *     name?: string,
     *     description?: string,
     *     format?: string,
     *     content?: array{
     *         subject?: string,
     *         text?: string,
     *         html?: string
     *     }
     * } $data Template data
     * @return array{id: string, created?: bool, updated?: bool}
     * @throws EmailEngineException
     */
    public function createOrUpdate(string $templateId, array $data): array
    {
        return $this->client->post("/v1/templates/{$templateId}", $data);
    }

    /**
     * Update a template
     *
     * @param string $templateId Template identifier
     * @param array{
     *     name?: string,
     *     description?: string,
     *     format?: string,
     *     content?: array{
     *         subject?: string,
     *         text?: string,
     *         html?: string
     *     }
     * } $data Template data
     * @return array{id: string, updated?: bool}
     * @throws EmailEngineException
     */
    public function update(string $templateId, array $data): array
    {
        return $this->client->put("/v1/templates/{$templateId}", $data);
    }

    /**
     * Delete a template
     *
     * @param string $templateId Template identifier
     * @return array{id: string, deleted: bool}
     * @throws EmailEngineException
     */
    public function delete(string $templateId): array
    {
        return $this->client->delete("/v1/templates/{$templateId}");
    }

    /**
     * List templates for an account
     *
     * @param string $accountId Account identifier
     * @param array{page?: int, pageSize?: int} $params Query parameters
     * @return array{templates: array, total?: int}
     * @throws EmailEngineException
     */
    public function listForAccount(string $accountId, array $params = []): array
    {
        return $this->client->get("/v1/templates/account/{$accountId}", $params);
    }

    /**
     * Delete all templates for an account
     *
     * @param string $accountId Account identifier
     * @return array{account: string, deleted: int}
     * @throws EmailEngineException
     */
    public function deleteForAccount(string $accountId): array
    {
        return $this->client->delete("/v1/templates/account/{$accountId}");
    }
}

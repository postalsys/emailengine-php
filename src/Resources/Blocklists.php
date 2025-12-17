<?php

declare(strict_types=1);

namespace Postalsys\EmailEnginePhp\Resources;

use Postalsys\EmailEnginePhp\HttpClient;
use Postalsys\EmailEnginePhp\Exceptions\EmailEngineException;

/**
 * Blocklist management resource
 *
 * Handles blocklist operations for managing blocked senders/recipients.
 */
class Blocklists
{
    public function __construct(
        private readonly HttpClient $client,
    ) {
    }

    /**
     * List all blocklists
     *
     * @return array{blocklists: array<array{
     *     listId: string,
     *     name?: string,
     *     description?: string,
     *     entries?: int
     * }>}
     * @throws EmailEngineException
     */
    public function list(): array
    {
        return $this->client->get('/v1/blocklists');
    }

    /**
     * Get blocklist entries
     *
     * @param string $listId Blocklist identifier
     * @param array{page?: int, pageSize?: int} $params Query parameters
     * @return array{
     *     entries: array<array{
     *         id: string,
     *         address?: string,
     *         reason?: string,
     *         created?: string
     *     }>,
     *     total?: int,
     *     page?: int,
     *     pages?: int
     * }
     * @throws EmailEngineException
     */
    public function get(string $listId, array $params = []): array
    {
        return $this->client->get("/v1/blocklist/{$listId}", $params);
    }

    /**
     * Add entry to blocklist
     *
     * @param string $listId Blocklist identifier
     * @param array{
     *     address: string,
     *     reason?: string
     * } $data Entry data
     * @return array{listId: string, id: string, created: bool}
     * @throws EmailEngineException
     */
    public function add(string $listId, array $data): array
    {
        return $this->client->post("/v1/blocklist/{$listId}", $data);
    }

    /**
     * Remove entry from blocklist
     *
     * @param string $listId Blocklist identifier
     * @param string $address Address to remove
     * @return array{listId: string, address: string, deleted: bool}
     * @throws EmailEngineException
     */
    public function remove(string $listId, string $address): array
    {
        return $this->client->delete("/v1/blocklist/{$listId}", [
            'address' => $address,
        ]);
    }
}

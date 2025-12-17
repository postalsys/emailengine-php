<?php

declare(strict_types=1);

namespace Postalsys\EmailEnginePhp\Resources;

use Postalsys\EmailEnginePhp\HttpClient;
use Postalsys\EmailEnginePhp\Exceptions\EmailEngineException;

/**
 * SMTP Gateway management resource
 *
 * Handles gateway operations including creating, listing,
 * updating, and deleting SMTP gateways.
 */
class Gateways
{
    public function __construct(
        private readonly HttpClient $client,
    ) {
    }

    /**
     * List all gateways
     *
     * @param array{page?: int, pageSize?: int} $params Query parameters
     * @return array{
     *     gateways: array<array{
     *         gateway: string,
     *         name?: string,
     *         host?: string,
     *         port?: int,
     *         deliveries?: int,
     *         lastUse?: string
     *     }>,
     *     total?: int,
     *     page?: int,
     *     pages?: int
     * }
     * @throws EmailEngineException
     */
    public function list(array $params = []): array
    {
        return $this->client->get('/v1/gateways', $params);
    }

    /**
     * Get gateway details
     *
     * @param string $gatewayId Gateway identifier
     * @return array{
     *     gateway: string,
     *     name?: string,
     *     host?: string,
     *     port?: int,
     *     secure?: bool,
     *     auth?: array{user?: string},
     *     deliveries?: int,
     *     lastUse?: string
     * }
     * @throws EmailEngineException
     */
    public function get(string $gatewayId): array
    {
        return $this->client->get("/v1/gateway/{$gatewayId}");
    }

    /**
     * Create a new gateway
     *
     * @param array{
     *     gateway?: string,
     *     name?: string,
     *     host: string,
     *     port: int,
     *     secure?: bool,
     *     auth?: array{user: string, pass: string}
     * } $data Gateway configuration
     * @return array{gateway: string}
     * @throws EmailEngineException
     */
    public function create(array $data): array
    {
        return $this->client->post('/v1/gateway', $data);
    }

    /**
     * Update a gateway
     *
     * @param string $gatewayId Gateway identifier
     * @param array{
     *     name?: string,
     *     host?: string,
     *     port?: int,
     *     secure?: bool,
     *     auth?: array{user?: string, pass?: string}
     * } $data Gateway configuration
     * @return array{gateway: string}
     * @throws EmailEngineException
     */
    public function update(string $gatewayId, array $data): array
    {
        return $this->client->put("/v1/gateway/edit/{$gatewayId}", $data);
    }

    /**
     * Delete a gateway
     *
     * @param string $gatewayId Gateway identifier
     * @return array{gateway: string, deleted: bool}
     * @throws EmailEngineException
     */
    public function delete(string $gatewayId): array
    {
        return $this->client->delete("/v1/gateway/{$gatewayId}");
    }
}

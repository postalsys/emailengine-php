<?php

declare(strict_types=1);

namespace Postalsys\EmailEnginePhp\Resources;

use Postalsys\EmailEnginePhp\HttpClient;
use Postalsys\EmailEnginePhp\Exceptions\EmailEngineException;

/**
 * Webhook routes management resource
 *
 * Handles webhook route operations for routing webhooks to
 * different endpoints based on account or other criteria.
 */
class Webhooks
{
    public function __construct(
        private readonly HttpClient $client,
    ) {
    }

    /**
     * List all webhook routes
     *
     * @param array{page?: int, pageSize?: int} $params Query parameters
     * @return array{
     *     webhookRoutes: array<array{
     *         id: string,
     *         name?: string,
     *         description?: string,
     *         enabled?: bool,
     *         url?: string,
     *         events?: array<string>,
     *         targetAccounts?: array<string>
     *     }>,
     *     total?: int,
     *     page?: int,
     *     pages?: int
     * }
     * @throws EmailEngineException
     */
    public function listRoutes(array $params = []): array
    {
        return $this->client->get('/v1/webhookRoutes', $params);
    }

    /**
     * Get webhook route details
     *
     * @param string $routeId Webhook route identifier
     * @return array{
     *     id: string,
     *     name?: string,
     *     description?: string,
     *     enabled?: bool,
     *     url?: string,
     *     events?: array<string>,
     *     targetAccounts?: array<string>,
     *     headers?: array<string, string>,
     *     created?: string,
     *     updated?: string
     * }
     * @throws EmailEngineException
     */
    public function getRoute(string $routeId): array
    {
        return $this->client->get("/v1/webhookRoutes/webhookRoute/{$routeId}");
    }
}

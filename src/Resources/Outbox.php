<?php

declare(strict_types=1);

namespace Postalsys\EmailEnginePhp\Resources;

use Postalsys\EmailEnginePhp\HttpClient;
use Postalsys\EmailEnginePhp\Exceptions\EmailEngineException;

/**
 * Outbox management resource
 *
 * Handles queued message operations including listing, viewing,
 * and canceling scheduled emails.
 */
class Outbox
{
    public function __construct(
        private readonly HttpClient $client,
    ) {
    }

    /**
     * List queued messages
     *
     * @param array{
     *     page?: int,
     *     pageSize?: int,
     *     account?: string
     * } $params Query parameters
     * @return array{
     *     messages: array<array{
     *         queueId: string,
     *         account: string,
     *         messageId?: string,
     *         subject?: string,
     *         created?: string,
     *         scheduled?: string,
     *         to?: array
     *     }>,
     *     total?: int,
     *     page?: int,
     *     pages?: int
     * }
     * @throws EmailEngineException
     */
    public function list(array $params = []): array
    {
        return $this->client->get('/v1/outbox', $params);
    }

    /**
     * Get queued message details
     *
     * @param string $queueId Queue item identifier
     * @return array{
     *     queueId: string,
     *     account: string,
     *     messageId?: string,
     *     subject?: string,
     *     from?: array,
     *     to?: array,
     *     created?: string,
     *     scheduled?: string,
     *     data?: array
     * }
     * @throws EmailEngineException
     */
    public function get(string $queueId): array
    {
        return $this->client->get("/v1/outbox/{$queueId}");
    }

    /**
     * Cancel a queued message
     *
     * @param string $queueId Queue item identifier
     * @return array{queueId: string, deleted: bool}
     * @throws EmailEngineException
     */
    public function cancel(string $queueId): array
    {
        return $this->client->delete("/v1/outbox/{$queueId}");
    }
}

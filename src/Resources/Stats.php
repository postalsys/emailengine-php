<?php

declare(strict_types=1);

namespace Postalsys\EmailEnginePhp\Resources;

use Postalsys\EmailEnginePhp\HttpClient;
use Postalsys\EmailEnginePhp\Exceptions\EmailEngineException;

/**
 * Statistics and utility resource
 *
 * Handles system statistics, auto-configuration, and license management.
 */
class Stats
{
    public function __construct(
        private readonly HttpClient $client,
    ) {
    }

    /**
     * Get system statistics
     *
     * @return array{
     *     version?: string,
     *     license?: string,
     *     accounts?: int,
     *     connections?: array{init?: int, connecting?: int, connected?: int, authenticationError?: int, connectError?: int},
     *     redis?: array{connected?: bool, memory?: string},
     *     queues?: array
     * }
     * @throws EmailEngineException
     */
    public function get(): array
    {
        return $this->client->get('/v1/stats');
    }

    /**
     * Auto-discover email settings for a domain
     *
     * @param string $email Email address to look up
     * @return array{
     *     imap?: array{host: string, port: int, secure: bool},
     *     smtp?: array{host: string, port: int, secure: bool}
     * }
     * @throws EmailEngineException
     */
    public function autoconfig(string $email): array
    {
        return $this->client->get('/v1/autoconfig', ['email' => $email]);
    }

    /**
     * Get license information
     *
     * @return array{
     *     active?: bool,
     *     type?: string,
     *     details?: array
     * }
     * @throws EmailEngineException
     */
    public function getLicense(): array
    {
        return $this->client->get('/v1/license');
    }

    /**
     * Register or update license
     *
     * @param string $license License key
     * @return array{active: bool, type?: string}
     * @throws EmailEngineException
     */
    public function setLicense(string $license): array
    {
        return $this->client->post('/v1/license', ['license' => $license]);
    }

    /**
     * Remove license
     *
     * @return array{deleted: bool}
     * @throws EmailEngineException
     */
    public function deleteLicense(): array
    {
        return $this->client->delete('/v1/license');
    }

    /**
     * Get change log / activity stream
     *
     * @param array{cursor?: string} $params Query parameters
     * @return array{changes: array, cursor?: string}
     * @throws EmailEngineException
     */
    public function getChanges(array $params = []): array
    {
        return $this->client->get('/v1/changes', $params);
    }
}

<?php

declare(strict_types=1);

namespace Postalsys\EmailEnginePhp\Resources;

use Postalsys\EmailEnginePhp\HttpClient;
use Postalsys\EmailEnginePhp\Exceptions\EmailEngineException;

/**
 * Mailbox management resource
 *
 * Handles all mailbox-related API operations including listing, creating,
 * updating, and deleting mailboxes.
 */
class Mailboxes
{
    public function __construct(
        private readonly HttpClient $client,
    ) {
    }

    /**
     * List mailboxes for an account
     *
     * @param string $accountId Account identifier
     * @param array{counters?: bool, path?: string} $params Query parameters
     * @return array{mailboxes: array<array{
     *     path: string,
     *     name: string,
     *     delimiter?: string,
     *     listed?: bool,
     *     specialUse?: string,
     *     noInferiors?: bool,
     *     subscribed?: bool,
     *     messages?: int,
     *     uidNext?: int,
     *     uidValidity?: int
     * }>}
     * @throws EmailEngineException
     */
    public function list(string $accountId, array $params = []): array
    {
        return $this->client->get("/v1/account/{$accountId}/mailboxes", $params);
    }

    /**
     * Create a new mailbox
     *
     * @param string $accountId Account identifier
     * @param string $path Mailbox path (e.g., "INBOX/Subfolder")
     * @return array{path: string, created: bool}
     * @throws EmailEngineException
     */
    public function create(string $accountId, string $path): array
    {
        return $this->client->post("/v1/account/{$accountId}/mailbox", [
            'path' => $path,
        ]);
    }

    /**
     * Rename a mailbox
     *
     * @param string $accountId Account identifier
     * @param string $path Current mailbox path
     * @param string $newPath New mailbox path
     * @return array{path: string, newPath: string, renamed: bool}
     * @throws EmailEngineException
     */
    public function rename(string $accountId, string $path, string $newPath): array
    {
        return $this->client->put("/v1/account/{$accountId}/mailbox", [
            'path' => $path,
            'newPath' => $newPath,
        ]);
    }

    /**
     * Delete a mailbox
     *
     * @param string $accountId Account identifier
     * @param string $path Mailbox path to delete
     * @return array{path: string, deleted: bool}
     * @throws EmailEngineException
     */
    public function delete(string $accountId, string $path): array
    {
        return $this->client->delete("/v1/account/{$accountId}/mailbox", [
            'path' => $path,
        ]);
    }

    /**
     * Subscribe to a mailbox
     *
     * @param string $accountId Account identifier
     * @param string $path Mailbox path
     * @return array{path: string, subscribed: bool}
     * @throws EmailEngineException
     */
    public function subscribe(string $accountId, string $path): array
    {
        return $this->client->put("/v1/account/{$accountId}/mailbox", [
            'path' => $path,
            'subscribed' => true,
        ]);
    }

    /**
     * Unsubscribe from a mailbox
     *
     * @param string $accountId Account identifier
     * @param string $path Mailbox path
     * @return array{path: string, subscribed: bool}
     * @throws EmailEngineException
     */
    public function unsubscribe(string $accountId, string $path): array
    {
        return $this->client->put("/v1/account/{$accountId}/mailbox", [
            'path' => $path,
            'subscribed' => false,
        ]);
    }
}

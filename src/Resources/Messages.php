<?php

declare(strict_types=1);

namespace Postalsys\EmailEnginePhp\Resources;

use Postalsys\EmailEnginePhp\HttpClient;
use Postalsys\EmailEnginePhp\Exceptions\EmailEngineException;

/**
 * Message management resource
 *
 * Handles all message-related API operations including listing, reading,
 * updating, deleting, searching, and sending messages.
 */
class Messages
{
    public function __construct(
        private readonly HttpClient $client,
    ) {
    }

    /**
     * List messages in a mailbox
     *
     * @param string $accountId Account identifier
     * @param array{
     *     path?: string,
     *     page?: int,
     *     pageSize?: int,
     *     cursor?: string,
     *     documentStore?: bool
     * } $params Query parameters
     * @return array{
     *     messages: array,
     *     total?: int,
     *     page?: int,
     *     pages?: int,
     *     nextPageCursor?: string,
     *     prevPageCursor?: string
     * }
     * @throws EmailEngineException
     */
    public function list(string $accountId, array $params = []): array
    {
        return $this->client->get("/v1/account/{$accountId}/messages", $params);
    }

    /**
     * Get message details
     *
     * @param string $accountId Account identifier
     * @param string $messageId Message identifier
     * @param array{
     *     textType?: string,
     *     embedAttachedImages?: bool,
     *     documentStore?: bool
     * } $params Query parameters
     * @return array{
     *     id: string,
     *     uid?: int,
     *     emailId?: string,
     *     threadId?: string,
     *     date?: string,
     *     subject?: string,
     *     from?: array,
     *     to?: array,
     *     cc?: array,
     *     bcc?: array,
     *     messageId?: string,
     *     flags?: array,
     *     labels?: array,
     *     attachments?: array,
     *     text?: array,
     *     html?: array
     * }
     * @throws EmailEngineException
     */
    public function get(string $accountId, string $messageId, array $params = []): array
    {
        return $this->client->get("/v1/account/{$accountId}/message/{$messageId}", $params);
    }

    /**
     * Get raw message source
     *
     * @param string $accountId Account identifier
     * @param string $messageId Message identifier
     * @return array{source: string}
     * @throws EmailEngineException
     */
    public function getSource(string $accountId, string $messageId): array
    {
        return $this->client->get("/v1/account/{$accountId}/message/{$messageId}/source");
    }

    /**
     * Get message text content
     *
     * @param string $accountId Account identifier
     * @param string $textId Text identifier
     * @param array{textType?: string} $params Query parameters
     * @return array{plain?: string, html?: string, hasMore?: bool}
     * @throws EmailEngineException
     */
    public function getText(string $accountId, string $textId, array $params = []): array
    {
        return $this->client->get("/v1/account/{$accountId}/text/{$textId}", $params);
    }

    /**
     * Update message flags
     *
     * @param string $accountId Account identifier
     * @param string $messageId Message identifier
     * @param array{
     *     flags?: array{add?: array<string>, delete?: array<string>, set?: array<string>},
     *     labels?: array{add?: array<string>, delete?: array<string>, set?: array<string>}
     * } $data Flags/labels to update
     * @return array{flags?: array, labels?: array}
     * @throws EmailEngineException
     */
    public function update(string $accountId, string $messageId, array $data): array
    {
        return $this->client->put("/v1/account/{$accountId}/message/{$messageId}", $data);
    }

    /**
     * Move message to another folder
     *
     * @param string $accountId Account identifier
     * @param string $messageId Message identifier
     * @param string $path Destination folder path
     * @return array{path: string, id?: string, uid?: int}
     * @throws EmailEngineException
     */
    public function move(string $accountId, string $messageId, string $path): array
    {
        return $this->client->put("/v1/account/{$accountId}/message/{$messageId}/move", [
            'path' => $path,
        ]);
    }

    /**
     * Delete a message
     *
     * @param string $accountId Account identifier
     * @param string $messageId Message identifier
     * @param bool $force If true, permanently delete instead of moving to trash
     * @return array{deleted: bool}
     * @throws EmailEngineException
     */
    public function delete(string $accountId, string $messageId, bool $force = false): array
    {
        return $this->client->delete("/v1/account/{$accountId}/message/{$messageId}", [
            'force' => $force ? 'true' : 'false',
        ]);
    }

    /**
     * Update multiple messages
     *
     * @param string $accountId Account identifier
     * @param array{
     *     path?: string,
     *     search?: array,
     *     messages?: array<string>,
     *     flags?: array{add?: array<string>, delete?: array<string>, set?: array<string>},
     *     labels?: array{add?: array<string>, delete?: array<string>, set?: array<string>}
     * } $data Messages and flags to update
     * @return array{path?: string, updated?: int}
     * @throws EmailEngineException
     */
    public function bulkUpdate(string $accountId, array $data): array
    {
        return $this->client->put("/v1/account/{$accountId}/messages", $data);
    }

    /**
     * Move multiple messages
     *
     * @param string $accountId Account identifier
     * @param array{
     *     path?: string,
     *     destination: string,
     *     search?: array,
     *     messages?: array<string>
     * } $data Messages to move
     * @return array{path?: string, destination: string, moved?: int}
     * @throws EmailEngineException
     */
    public function bulkMove(string $accountId, array $data): array
    {
        return $this->client->put("/v1/account/{$accountId}/messages/move", $data);
    }

    /**
     * Delete multiple messages
     *
     * @param string $accountId Account identifier
     * @param array{
     *     path?: string,
     *     search?: array,
     *     messages?: array<string>,
     *     force?: bool
     * } $data Messages to delete
     * @return array{path?: string, deleted?: int}
     * @throws EmailEngineException
     */
    public function bulkDelete(string $accountId, array $data): array
    {
        return $this->client->put("/v1/account/{$accountId}/messages/delete", $data);
    }

    /**
     * Search messages
     *
     * @param string $accountId Account identifier
     * @param array{
     *     path?: string,
     *     page?: int,
     *     pageSize?: int,
     *     documentStore?: bool,
     *     search?: array{
     *         unseen?: bool,
     *         flagged?: bool,
     *         answered?: bool,
     *         draft?: bool,
     *         seq?: string,
     *         uid?: string,
     *         from?: string,
     *         to?: string,
     *         cc?: string,
     *         bcc?: string,
     *         body?: string,
     *         subject?: string,
     *         larger?: int,
     *         smaller?: int,
     *         sentBefore?: string,
     *         sentSince?: string,
     *         emailId?: string,
     *         threadId?: string,
     *         header?: array
     *     }
     * } $data Search criteria
     * @return array{
     *     messages: array,
     *     total?: int,
     *     page?: int,
     *     pages?: int,
     *     nextPageCursor?: string,
     *     prevPageCursor?: string
     * }
     * @throws EmailEngineException
     */
    public function search(string $accountId, array $data = []): array
    {
        return $this->client->post("/v1/account/{$accountId}/search", $data);
    }

    /**
     * Search across multiple accounts
     *
     * @param array{
     *     accounts?: array<string>,
     *     query?: string,
     *     page?: int,
     *     pageSize?: int
     * } $data Search criteria
     * @return array{messages: array, total?: int}
     * @throws EmailEngineException
     */
    public function unifiedSearch(array $data = []): array
    {
        return $this->client->post('/v1/unified/search', $data);
    }

    /**
     * Upload/create a new message (draft)
     *
     * @param string $accountId Account identifier
     * @param array{
     *     path?: string,
     *     flags?: array<string>,
     *     raw?: string,
     *     from?: array{name?: string, address: string},
     *     to?: array<array{name?: string, address: string}>,
     *     cc?: array<array{name?: string, address: string}>,
     *     bcc?: array<array{name?: string, address: string}>,
     *     subject?: string,
     *     text?: string,
     *     html?: string,
     *     attachments?: array
     * } $data Message data
     * @return array{id?: string, uid?: int, path?: string}
     * @throws EmailEngineException
     */
    public function create(string $accountId, array $data): array
    {
        return $this->client->post("/v1/account/{$accountId}/message", $data);
    }

    /**
     * Submit message for delivery
     *
     * @param string $accountId Account identifier
     * @param array{
     *     from?: array{name?: string, address: string},
     *     to?: array<array{name?: string, address: string}>,
     *     cc?: array<array{name?: string, address: string}>,
     *     bcc?: array<array{name?: string, address: string}>,
     *     replyTo?: array<array{name?: string, address: string}>,
     *     subject?: string,
     *     text?: string,
     *     html?: string,
     *     attachments?: array<array{filename?: string, content?: string, contentType?: string}>,
     *     headers?: array<string, string>,
     *     reference?: array{message: string, action: string, inline?: bool},
     *     template?: string,
     *     render?: array<string, mixed>,
     *     mailMerge?: array<array{to: array, params?: array}>,
     *     sendAt?: string,
     *     copy?: bool,
     *     trackingEnabled?: bool,
     *     dsn?: array
     * } $data Message data
     * @param array{idempotencyKey?: string, timeout?: int} $options Request options
     * @return array{
     *     response?: string,
     *     messageId?: string,
     *     sendAt?: string,
     *     queueId?: string
     * }
     * @throws EmailEngineException
     */
    public function submit(string $accountId, array $data, array $options = []): array
    {
        $headers = [];

        if (isset($options['idempotencyKey'])) {
            $headers['Idempotency-Key'] = $options['idempotencyKey'];
        }

        if (isset($options['timeout'])) {
            $headers['X-EE-Timeout'] = (string) $options['timeout'];
        }

        return $this->client->post("/v1/account/{$accountId}/submit", $data, $headers);
    }

    /**
     * Download attachment
     *
     * @param string $accountId Account identifier
     * @param string $attachmentId Attachment identifier
     * @return array{content?: string, contentType?: string, filename?: string}
     * @throws EmailEngineException
     */
    public function getAttachment(string $accountId, string $attachmentId): array
    {
        return $this->client->get("/v1/account/{$accountId}/attachment/{$attachmentId}");
    }
}

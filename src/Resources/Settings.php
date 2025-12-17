<?php

declare(strict_types=1);

namespace Postalsys\EmailEnginePhp\Resources;

use Postalsys\EmailEnginePhp\HttpClient;
use Postalsys\EmailEnginePhp\Exceptions\EmailEngineException;

/**
 * Settings management resource
 *
 * Handles system settings and webhook configuration.
 */
class Settings
{
    public function __construct(
        private readonly HttpClient $client,
    ) {
    }

    /**
     * Get system settings
     *
     * @param array<string> $keys Specific setting keys to retrieve (empty for all)
     * @return array<string, mixed>
     * @throws EmailEngineException
     */
    public function get(array $keys = []): array
    {
        $query = [];
        foreach ($keys as $key) {
            $query[$key] = 'true';
        }

        return $this->client->get('/v1/settings', $query);
    }

    /**
     * Update system settings
     *
     * @param array<string, mixed> $data Settings to update
     * @return array{updated: bool}
     * @throws EmailEngineException
     */
    public function update(array $data): array
    {
        return $this->client->post('/v1/settings', $data);
    }

    /**
     * Get webhook settings
     *
     * @return array{
     *     enabled: bool,
     *     url: string,
     *     events: array<string>,
     *     headers: array<string>,
     *     text: int|false
     * }
     * @throws EmailEngineException
     */
    public function getWebhooks(): array
    {
        $data = $this->get([
            'webhooksEnabled',
            'webhookEvents',
            'webhooks',
            'notifyHeaders',
            'notifyText',
            'notifyTextSize',
        ]);

        return [
            'enabled' => !empty($data['webhooksEnabled']),
            'url' => $data['webhooks'] ?? '',
            'events' => $data['webhookEvents'] ?? [],
            'headers' => $data['notifyHeaders'] ?? [],
            'text' => !empty($data['notifyText']) ? ($data['notifyTextSize'] ?? 0) : false,
        ];
    }

    /**
     * Update webhook settings
     *
     * @param array{
     *     enabled?: bool,
     *     url?: string,
     *     events?: array<string>,
     *     headers?: array<string>,
     *     text?: int|bool
     * } $options Webhook settings
     * @return bool True if update was successful
     * @throws EmailEngineException
     */
    public function setWebhooks(array $options): bool
    {
        $settings = [];

        if (isset($options['enabled'])) {
            $settings['webhooksEnabled'] = (bool) $options['enabled'];
        }

        if (isset($options['url'])) {
            $settings['webhooks'] = $options['url'];
        }

        if (isset($options['events'])) {
            $settings['webhookEvents'] = $options['events'];
        }

        if (isset($options['headers'])) {
            $settings['notifyHeaders'] = $options['headers'];
        }

        if (isset($options['text'])) {
            if (empty($options['text']) || $options['text'] === 0) {
                $settings['notifyText'] = false;
            } else {
                $settings['notifyText'] = true;
                if (is_numeric($options['text'])) {
                    $settings['notifyTextSize'] = (int) $options['text'];
                }
            }
        }

        $data = $this->update($settings);

        return isset($data['updated']);
    }

    /**
     * Get queue settings
     *
     * @param string $queue Queue name
     * @return array<string, mixed>
     * @throws EmailEngineException
     */
    public function getQueue(string $queue): array
    {
        return $this->client->get("/v1/settings/queue/{$queue}");
    }

    /**
     * Update queue settings
     *
     * @param string $queue Queue name
     * @param array<string, mixed> $data Queue settings
     * @return array{updated: bool}
     * @throws EmailEngineException
     */
    public function setQueue(string $queue, array $data): array
    {
        return $this->client->put("/v1/settings/queue/{$queue}", $data);
    }
}

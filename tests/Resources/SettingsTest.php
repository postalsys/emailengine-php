<?php

declare(strict_types=1);

namespace Postalsys\EmailEnginePhp\Tests\Resources;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Postalsys\EmailEnginePhp\HttpClient;
use Postalsys\EmailEnginePhp\Resources\Settings;

class SettingsTest extends TestCase
{
    private function createSettingsWithMockHandler(array $responses): Settings
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handlerStack]);

        $httpClient = new HttpClient(
            baseUrl: 'http://localhost:3000',
            accessToken: 'test-token'
        );
        $httpClient->setGuzzleClient($guzzle);

        return new Settings($httpClient);
    }

    public function testGetSettings(): void
    {
        $settings = $this->createSettingsWithMockHandler([
            new Response(200, [], json_encode([
                'webhooksEnabled' => true,
                'webhooks' => 'http://webhook.example.com',
            ])),
        ]);

        $result = $settings->get(['webhooksEnabled', 'webhooks']);

        $this->assertTrue($result['webhooksEnabled']);
        $this->assertEquals('http://webhook.example.com', $result['webhooks']);
    }

    public function testUpdateSettings(): void
    {
        $settings = $this->createSettingsWithMockHandler([
            new Response(200, [], json_encode([
                'updated' => true,
            ])),
        ]);

        $result = $settings->update([
            'webhooksEnabled' => true,
            'webhooks' => 'http://new-webhook.example.com',
        ]);

        $this->assertTrue($result['updated']);
    }

    public function testGetWebhooks(): void
    {
        $settings = $this->createSettingsWithMockHandler([
            new Response(200, [], json_encode([
                'webhooksEnabled' => true,
                'webhooks' => 'http://webhook.example.com',
                'webhookEvents' => ['messageNew', 'messageUpdated'],
                'notifyHeaders' => ['Received', 'List-ID'],
                'notifyText' => true,
                'notifyTextSize' => 1024,
            ])),
        ]);

        $result = $settings->getWebhooks();

        $this->assertTrue($result['enabled']);
        $this->assertEquals('http://webhook.example.com', $result['url']);
        $this->assertEquals(['messageNew', 'messageUpdated'], $result['events']);
        $this->assertEquals(['Received', 'List-ID'], $result['headers']);
        $this->assertEquals(1024, $result['text']);
    }

    public function testGetWebhooksDisabled(): void
    {
        $settings = $this->createSettingsWithMockHandler([
            new Response(200, [], json_encode([
                'webhooksEnabled' => false,
                'webhooks' => '',
                'webhookEvents' => [],
                'notifyHeaders' => [],
                'notifyText' => false,
            ])),
        ]);

        $result = $settings->getWebhooks();

        $this->assertFalse($result['enabled']);
        $this->assertEquals('', $result['url']);
        $this->assertEmpty($result['events']);
        $this->assertFalse($result['text']);
    }

    public function testSetWebhooksEnabled(): void
    {
        $settings = $this->createSettingsWithMockHandler([
            new Response(200, [], json_encode([
                'updated' => true,
            ])),
        ]);

        $result = $settings->setWebhooks([
            'enabled' => true,
            'url' => 'http://webhook.example.com',
            'events' => ['*'],
        ]);

        $this->assertTrue($result);
    }

    public function testSetWebhooksWithText(): void
    {
        $settings = $this->createSettingsWithMockHandler([
            new Response(200, [], json_encode([
                'updated' => true,
            ])),
        ]);

        $result = $settings->setWebhooks([
            'enabled' => true,
            'url' => 'http://webhook.example.com',
            'events' => ['messageNew'],
            'headers' => ['List-ID'],
            'text' => 2048,
        ]);

        $this->assertTrue($result);
    }

    public function testSetWebhooksDisableText(): void
    {
        $settings = $this->createSettingsWithMockHandler([
            new Response(200, [], json_encode([
                'updated' => true,
            ])),
        ]);

        $result = $settings->setWebhooks([
            'text' => false,
        ]);

        $this->assertTrue($result);
    }

    public function testGetQueue(): void
    {
        $settings = $this->createSettingsWithMockHandler([
            new Response(200, [], json_encode([
                'queue' => 'submit',
                'concurrency' => 10,
                'attempts' => 3,
            ])),
        ]);

        $result = $settings->getQueue('submit');

        $this->assertEquals(10, $result['concurrency']);
        $this->assertEquals(3, $result['attempts']);
    }

    public function testSetQueue(): void
    {
        $settings = $this->createSettingsWithMockHandler([
            new Response(200, [], json_encode([
                'updated' => true,
            ])),
        ]);

        $result = $settings->setQueue('submit', [
            'concurrency' => 20,
        ]);

        $this->assertTrue($result['updated']);
    }
}

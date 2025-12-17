<?php

declare(strict_types=1);

namespace Postalsys\EmailEnginePhp\Tests\Integration;

/**
 * Integration tests for Settings resource
 *
 * Tests settings and webhook configuration against a real EmailEngine instance.
 */
class SettingsIntegrationTest extends IntegrationTestCase
{
    private static ?array $originalWebhookSettings = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Save original webhook settings to restore later
        if (self::$client !== null) {
            try {
                self::$originalWebhookSettings = self::$client->settings->getWebhooks();
            } catch (\Exception $e) {
                self::$originalWebhookSettings = null;
            }
        }
    }

    public static function tearDownAfterClass(): void
    {
        // Restore original webhook settings
        if (self::$client !== null && self::$originalWebhookSettings !== null) {
            try {
                self::$client->settings->setWebhooks(self::$originalWebhookSettings);
            } catch (\Exception $e) {
                // Ignore restoration errors
            }
        }

        parent::tearDownAfterClass();
    }

    public function testGetSettings(): void
    {
        $result = $this->getClient()->settings->get([
            'webhooksEnabled',
            'webhooks',
        ]);

        $this->assertIsArray($result);
    }

    public function testGetWebhookSettings(): void
    {
        $result = $this->getClient()->settings->getWebhooks();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('enabled', $result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('events', $result);
        $this->assertArrayHasKey('headers', $result);
        $this->assertArrayHasKey('text', $result);
    }

    public function testSetWebhookSettings(): void
    {
        $testUrl = 'https://webhook-test-' . time() . '.example.com/hook';
        $testEvents = ['messageNew', 'messageUpdated'];

        $result = $this->getClient()->settings->setWebhooks([
            'enabled' => true,
            'url' => $testUrl,
            'events' => $testEvents,
            'headers' => ['X-Custom-Header'],
            'text' => 1024,
        ]);

        $this->assertTrue($result);

        // Verify the settings were applied
        $settings = $this->getClient()->settings->getWebhooks();

        $this->assertTrue($settings['enabled']);
        $this->assertEquals($testUrl, $settings['url']);
        $this->assertEquals($testEvents, $settings['events']);
        $this->assertEquals(['X-Custom-Header'], $settings['headers']);
        $this->assertEquals(1024, $settings['text']);
    }

    public function testDisableWebhooks(): void
    {
        $result = $this->getClient()->settings->setWebhooks([
            'enabled' => false,
        ]);

        $this->assertTrue($result);

        // Verify webhooks are disabled
        $settings = $this->getClient()->settings->getWebhooks();
        $this->assertFalse($settings['enabled']);
    }

    public function testUpdateSettings(): void
    {
        $result = $this->getClient()->settings->update([
            'webhooksEnabled' => false,
        ]);

        $this->assertIsArray($result);
        $this->assertTrue($result['updated'] ?? false);
    }
}

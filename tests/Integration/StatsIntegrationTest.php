<?php

declare(strict_types=1);

namespace Postalsys\EmailEnginePhp\Tests\Integration;

/**
 * Integration tests for Stats resource
 *
 * These tests verify connectivity and basic API functionality.
 * They should run first to ensure the EmailEngine instance is accessible.
 */
class StatsIntegrationTest extends IntegrationTestCase
{
    public function testGetStats(): void
    {
        $stats = $this->getClient()->stats->get();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('version', $stats);
        $this->assertArrayHasKey('accounts', $stats);
        $this->assertArrayHasKey('connections', $stats);
    }

    public function testGetStatsReturnsVersion(): void
    {
        $stats = $this->getClient()->stats->get();

        $this->assertNotEmpty($stats['version']);
        // Version should be in semver format
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', $stats['version']);
    }

    public function testGetLicenseInfo(): void
    {
        $license = $this->getClient()->stats->getLicense();

        $this->assertIsArray($license);
        // Without a license, active should be false or not present
        $this->assertFalse($license['active'] ?? false);
    }

    public function testAutoconfig(): void
    {
        // Test with a well-known email provider
        $config = $this->getClient()->stats->autoconfig('test@gmail.com');

        $this->assertIsArray($config);
        // Gmail should return IMAP/SMTP settings
        if (!empty($config['imap'])) {
            $this->assertArrayHasKey('host', $config['imap']);
            $this->assertArrayHasKey('port', $config['imap']);
        }
    }
}

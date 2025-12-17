<?php

declare(strict_types=1);

namespace Postalsys\EmailEnginePhp\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Postalsys\EmailEnginePhp\EmailEngine;

/**
 * Base class for integration tests
 *
 * Requires a running EmailEngine instance and valid access token.
 * Set environment variables:
 *   - EMAILENGINE_BASE_URL: EmailEngine API URL (default: http://localhost:3000)
 *   - EMAILENGINE_ACCESS_TOKEN: Valid API access token (required)
 *
 * IMPORTANT: EmailEngine without a license suspends workers after 15 minutes.
 * Integration tests must complete within this time window.
 */
abstract class IntegrationTestCase extends TestCase
{
    protected static ?EmailEngine $client = null;
    protected static string $baseUrl;
    protected static string $accessToken;

    public static function setUpBeforeClass(): void
    {
        self::$baseUrl = getenv('EMAILENGINE_BASE_URL') ?: 'http://localhost:3000';
        self::$accessToken = getenv('EMAILENGINE_ACCESS_TOKEN') ?: '';

        if (empty(self::$accessToken)) {
            self::markTestSkipped(
                'Integration tests require EMAILENGINE_ACCESS_TOKEN environment variable. ' .
                'Run: docker compose run --rm integration'
            );
        }

        self::$client = new EmailEngine(
            accessToken: self::$accessToken,
            baseUrl: self::$baseUrl,
            timeout: 30,
        );
    }

    protected function getClient(): EmailEngine
    {
        if (self::$client === null) {
            self::fail('EmailEngine client not initialized');
        }

        return self::$client;
    }

    /**
     * Generate a unique ID for test resources
     */
    protected function generateTestId(string $prefix = 'test'): string
    {
        return $prefix . '-' . bin2hex(random_bytes(4)) . '-' . time();
    }

    /**
     * Wait for a condition to be true (with timeout)
     *
     * @param callable $condition Function that returns bool
     * @param int $timeout Maximum wait time in seconds
     * @param int $interval Check interval in milliseconds
     */
    protected function waitFor(callable $condition, int $timeout = 30, int $interval = 500): bool
    {
        $start = time();

        while (time() - $start < $timeout) {
            if ($condition()) {
                return true;
            }
            usleep($interval * 1000);
        }

        return false;
    }
}

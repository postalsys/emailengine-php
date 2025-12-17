<?php

declare(strict_types=1);

namespace Postalsys\EmailEnginePhp\Tests\Integration;

/**
 * Integration tests for Tokens resource
 *
 * Tests access token management against a real EmailEngine instance.
 */
class TokensIntegrationTest extends IntegrationTestCase
{
    private static array $createdTokenIds = [];

    public static function tearDownAfterClass(): void
    {
        // Clean up any tokens created during tests
        if (self::$client !== null) {
            foreach (self::$createdTokenIds as $tokenId) {
                try {
                    self::$client->tokens->delete($tokenId);
                } catch (\Exception $e) {
                    // Ignore cleanup errors
                }
            }
        }

        parent::tearDownAfterClass();
    }

    public function testListTokens(): void
    {
        $result = $this->getClient()->tokens->list();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('tokens', $result);
        $this->assertIsArray($result['tokens']);

        // At least the token we're using should exist
        $this->assertNotEmpty($result['tokens']);
    }

    public function testCreateAccountToken(): void
    {
        // First, we need an account to create a token for
        // Create a temporary account
        $accountId = $this->generateTestId('token-test');

        try {
            $this->getClient()->accounts->create([
                'account' => $accountId,
                'name' => 'Token Test Account',
                'email' => 'tokentest@example.invalid',
                'imap' => [
                    'host' => 'imap.example.invalid',
                    'port' => 993,
                    'secure' => true,
                    'auth' => ['user' => 'test', 'pass' => 'test'],
                ],
            ]);

            // Create a token for this account
            $result = $this->getClient()->tokens->create([
                'account' => $accountId,
                'description' => 'Test Token ' . time(),
                'scopes' => ['api'],
            ]);

            $this->assertIsArray($result);
            $this->assertArrayHasKey('token', $result);
            $this->assertArrayHasKey('id', $result);
            $this->assertNotEmpty($result['token']);

            self::$createdTokenIds[] = $result['id'];

            // Verify we can list tokens for this account
            $accountTokens = $this->getClient()->tokens->listForAccount($accountId);
            $this->assertIsArray($accountTokens);
            $this->assertArrayHasKey('tokens', $accountTokens);

        } finally {
            // Clean up the test account
            try {
                $this->getClient()->accounts->delete($accountId);
            } catch (\Exception $e) {
                // Ignore
            }
        }
    }

    public function testTokenListContainsExpectedFields(): void
    {
        $result = $this->getClient()->tokens->list();

        if (empty($result['tokens'])) {
            $this->markTestSkipped('No tokens available to inspect');
        }

        $token = $result['tokens'][0];

        $this->assertArrayHasKey('id', $token);
        // Other fields may or may not be present depending on token type
    }
}

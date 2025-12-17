<?php

declare(strict_types=1);

namespace Postalsys\EmailEnginePhp\Tests\Integration;

use Postalsys\EmailEnginePhp\Exceptions\NotFoundException;
use Postalsys\EmailEnginePhp\Exceptions\ValidationException;

/**
 * Integration tests for Accounts resource
 *
 * Tests account CRUD operations against a real EmailEngine instance.
 */
class AccountsIntegrationTest extends IntegrationTestCase
{
    private static array $createdAccounts = [];

    public static function tearDownAfterClass(): void
    {
        // Clean up any accounts created during tests
        if (self::$client !== null) {
            foreach (self::$createdAccounts as $accountId) {
                try {
                    self::$client->accounts->delete($accountId);
                } catch (\Exception $e) {
                    // Ignore cleanup errors
                }
            }
        }

        parent::tearDownAfterClass();
    }

    public function testListAccountsEmpty(): void
    {
        $result = $this->getClient()->accounts->list();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('accounts', $result);
        $this->assertIsArray($result['accounts']);
    }

    public function testListAccountsWithPagination(): void
    {
        $result = $this->getClient()->accounts->list([
            'page' => 0,
            'pageSize' => 10,
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('accounts', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('pages', $result);
    }

    public function testCreateAccountWithInvalidCredentials(): void
    {
        $accountId = $this->generateTestId('account');

        try {
            // Create account with invalid credentials
            // This should create the account but it won't connect
            $result = $this->getClient()->accounts->create([
                'account' => $accountId,
                'name' => 'Test Account',
                'email' => 'test@example.invalid',
                'imap' => [
                    'host' => 'imap.example.invalid',
                    'port' => 993,
                    'secure' => true,
                    'auth' => [
                        'user' => 'testuser',
                        'pass' => 'testpass',
                    ],
                ],
                'smtp' => [
                    'host' => 'smtp.example.invalid',
                    'port' => 465,
                    'secure' => true,
                    'auth' => [
                        'user' => 'testuser',
                        'pass' => 'testpass',
                    ],
                ],
            ]);

            self::$createdAccounts[] = $accountId;

            $this->assertIsArray($result);
            $this->assertEquals($accountId, $result['account']);
            $this->assertArrayHasKey('state', $result);
        } catch (ValidationException $e) {
            // Some EmailEngine versions may reject invalid credentials immediately
            $this->assertNotEmpty($e->getMessage());
        }
    }

    /**
     * @depends testCreateAccountWithInvalidCredentials
     */
    public function testGetAccount(): void
    {
        if (empty(self::$createdAccounts)) {
            $this->markTestSkipped('No test account available');
        }

        $accountId = self::$createdAccounts[0];
        $result = $this->getClient()->accounts->get($accountId);

        $this->assertIsArray($result);
        $this->assertEquals($accountId, $result['account']);
        $this->assertArrayHasKey('state', $result);
        $this->assertArrayHasKey('email', $result);
    }

    /**
     * @depends testCreateAccountWithInvalidCredentials
     */
    public function testUpdateAccount(): void
    {
        if (empty(self::$createdAccounts)) {
            $this->markTestSkipped('No test account available');
        }

        $accountId = self::$createdAccounts[0];
        $newName = 'Updated Test Account ' . time();

        $result = $this->getClient()->accounts->update($accountId, [
            'name' => $newName,
        ]);

        $this->assertIsArray($result);
        $this->assertEquals($accountId, $result['account']);

        // Verify the update
        $account = $this->getClient()->accounts->get($accountId);
        $this->assertEquals($newName, $account['name']);
    }

    /**
     * @depends testUpdateAccount
     */
    public function testReconnectAccount(): void
    {
        if (empty(self::$createdAccounts)) {
            $this->markTestSkipped('No test account available');
        }

        $accountId = self::$createdAccounts[0];
        $result = $this->getClient()->accounts->reconnect($accountId);

        $this->assertIsArray($result);
        $this->assertEquals($accountId, $result['account']);
    }

    /**
     * @depends testReconnectAccount
     */
    public function testGetAccountLogs(): void
    {
        if (empty(self::$createdAccounts)) {
            $this->markTestSkipped('No test account available');
        }

        $accountId = self::$createdAccounts[0];

        // Wait a bit for logs to be generated
        sleep(1);

        $result = $this->getClient()->accounts->getLogs($accountId);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('logs', $result);
    }

    public function testGetNonExistentAccount(): void
    {
        $this->expectException(NotFoundException::class);

        $this->getClient()->accounts->get('non-existent-account-12345');
    }

    public function testVerifyAccountCredentials(): void
    {
        // This will fail because the server doesn't exist,
        // but it tests that the API endpoint works
        try {
            $result = $this->getClient()->accounts->verify([
                'imap' => [
                    'host' => 'imap.example.invalid',
                    'port' => 993,
                    'secure' => true,
                    'auth' => [
                        'user' => 'testuser',
                        'pass' => 'testpass',
                    ],
                ],
            ]);

            // If we get here, check the result
            $this->assertIsArray($result);
        } catch (ValidationException $e) {
            // Expected - invalid credentials
            $this->assertNotEmpty($e->getMessage());
        }
    }

    /**
     * @depends testGetAccountLogs
     */
    public function testDeleteAccount(): void
    {
        if (empty(self::$createdAccounts)) {
            $this->markTestSkipped('No test account available');
        }

        $accountId = array_pop(self::$createdAccounts);
        $result = $this->getClient()->accounts->delete($accountId);

        $this->assertIsArray($result);
        $this->assertTrue($result['deleted']);

        // Verify deletion
        $this->expectException(NotFoundException::class);
        $this->getClient()->accounts->get($accountId);
    }
}

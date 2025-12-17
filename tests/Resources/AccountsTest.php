<?php

declare(strict_types=1);

namespace Postalsys\EmailEnginePhp\Tests\Resources;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Postalsys\EmailEnginePhp\HttpClient;
use Postalsys\EmailEnginePhp\Resources\Accounts;

class AccountsTest extends TestCase
{
    private function createAccountsWithMockHandler(array $responses): Accounts
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handlerStack]);

        $httpClient = new HttpClient(
            baseUrl: 'http://localhost:3000',
            accessToken: 'test-token'
        );
        $httpClient->setGuzzleClient($guzzle);

        return new Accounts($httpClient);
    }

    public function testCreateAccount(): void
    {
        $accounts = $this->createAccountsWithMockHandler([
            new Response(200, [], json_encode([
                'account' => 'new-account',
                'state' => 'init',
            ])),
        ]);

        $result = $accounts->create([
            'account' => 'new-account',
            'name' => 'Test Account',
            'email' => 'test@example.com',
            'imap' => [
                'host' => 'imap.example.com',
                'port' => 993,
                'secure' => true,
                'auth' => ['user' => 'test', 'pass' => 'password'],
            ],
            'smtp' => [
                'host' => 'smtp.example.com',
                'port' => 465,
                'secure' => true,
                'auth' => ['user' => 'test', 'pass' => 'password'],
            ],
        ]);

        $this->assertEquals('new-account', $result['account']);
        $this->assertEquals('init', $result['state']);
    }

    public function testGetAccount(): void
    {
        $accounts = $this->createAccountsWithMockHandler([
            new Response(200, [], json_encode([
                'account' => 'test-account',
                'name' => 'Test Account',
                'email' => 'test@example.com',
                'state' => 'connected',
                'counters' => ['messages' => 100, 'total' => 500],
            ])),
        ]);

        $result = $accounts->get('test-account');

        $this->assertEquals('test-account', $result['account']);
        $this->assertEquals('connected', $result['state']);
        $this->assertEquals(100, $result['counters']['messages']);
    }

    public function testListAccounts(): void
    {
        $accounts = $this->createAccountsWithMockHandler([
            new Response(200, [], json_encode([
                'accounts' => [
                    ['account' => 'account-1', 'state' => 'connected'],
                    ['account' => 'account-2', 'state' => 'connecting'],
                ],
                'total' => 2,
                'page' => 0,
                'pages' => 1,
            ])),
        ]);

        $result = $accounts->list(['page' => 0, 'pageSize' => 20]);

        $this->assertCount(2, $result['accounts']);
        $this->assertEquals(2, $result['total']);
    }

    public function testListAccountsWithFilters(): void
    {
        $accounts = $this->createAccountsWithMockHandler([
            new Response(200, [], json_encode([
                'accounts' => [
                    ['account' => 'account-1', 'state' => 'connected'],
                ],
                'total' => 1,
            ])),
        ]);

        $result = $accounts->list([
            'state' => 'connected',
            'query' => 'test',
        ]);

        $this->assertCount(1, $result['accounts']);
    }

    public function testUpdateAccount(): void
    {
        $accounts = $this->createAccountsWithMockHandler([
            new Response(200, [], json_encode([
                'account' => 'test-account',
            ])),
        ]);

        $result = $accounts->update('test-account', [
            'name' => 'Updated Name',
            'webhooks' => 'http://webhook.example.com',
        ]);

        $this->assertEquals('test-account', $result['account']);
    }

    public function testDeleteAccount(): void
    {
        $accounts = $this->createAccountsWithMockHandler([
            new Response(200, [], json_encode([
                'account' => 'test-account',
                'deleted' => true,
            ])),
        ]);

        $result = $accounts->delete('test-account');

        $this->assertTrue($result['deleted']);
    }

    public function testReconnectAccount(): void
    {
        $accounts = $this->createAccountsWithMockHandler([
            new Response(200, [], json_encode([
                'account' => 'test-account',
            ])),
        ]);

        $result = $accounts->reconnect('test-account');

        $this->assertEquals('test-account', $result['account']);
    }

    public function testSyncAccount(): void
    {
        $accounts = $this->createAccountsWithMockHandler([
            new Response(200, [], json_encode([
                'account' => 'test-account',
            ])),
        ]);

        $result = $accounts->sync('test-account');

        $this->assertEquals('test-account', $result['account']);
    }

    public function testFlushAccount(): void
    {
        $accounts = $this->createAccountsWithMockHandler([
            new Response(200, [], json_encode([
                'account' => 'test-account',
            ])),
        ]);

        $result = $accounts->flush('test-account', ['messages' => true]);

        $this->assertEquals('test-account', $result['account']);
    }

    public function testVerifyAccount(): void
    {
        $accounts = $this->createAccountsWithMockHandler([
            new Response(200, [], json_encode([
                'imap' => ['success' => true],
                'smtp' => ['success' => true],
            ])),
        ]);

        $result = $accounts->verify([
            'imap' => [
                'host' => 'imap.example.com',
                'port' => 993,
                'secure' => true,
                'auth' => ['user' => 'test', 'pass' => 'password'],
            ],
        ]);

        $this->assertTrue($result['imap']['success']);
    }

    public function testGetLogs(): void
    {
        $accounts = $this->createAccountsWithMockHandler([
            new Response(200, [], json_encode([
                'logs' => [
                    ['level' => 'info', 'message' => 'Connected'],
                ],
                'total' => 1,
            ])),
        ]);

        $result = $accounts->getLogs('test-account');

        $this->assertCount(1, $result['logs']);
    }

    public function testGetOAuthToken(): void
    {
        $accounts = $this->createAccountsWithMockHandler([
            new Response(200, [], json_encode([
                'accessToken' => 'oauth-token-123',
                'expires' => '2024-12-31T23:59:59Z',
            ])),
        ]);

        $result = $accounts->getOAuthToken('test-account');

        $this->assertEquals('oauth-token-123', $result['accessToken']);
    }

    public function testGetServerSignatures(): void
    {
        $accounts = $this->createAccountsWithMockHandler([
            new Response(200, [], json_encode([
                'signatures' => [
                    ['type' => 'DKIM', 'value' => 'example.com'],
                ],
            ])),
        ]);

        $result = $accounts->getServerSignatures('test-account');

        $this->assertCount(1, $result['signatures']);
    }

    public function testCreateDeliveryTest(): void
    {
        $accounts = $this->createAccountsWithMockHandler([
            new Response(200, [], json_encode([
                'deliveryTest' => 'test-123',
            ])),
        ]);

        $result = $accounts->createDeliveryTest('test-account', ['to' => 'test@example.com']);

        $this->assertEquals('test-123', $result['deliveryTest']);
    }

    public function testCheckDeliveryTest(): void
    {
        $accounts = $this->createAccountsWithMockHandler([
            new Response(200, [], json_encode([
                'deliveryTest' => 'test-123',
                'status' => 'completed',
            ])),
        ]);

        $result = $accounts->checkDeliveryTest('test-123');

        $this->assertEquals('completed', $result['status']);
    }
}

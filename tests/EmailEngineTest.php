<?php

declare(strict_types=1);

namespace Postalsys\EmailEnginePhp\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Postalsys\EmailEnginePhp\EmailEngine;
use Postalsys\EmailEnginePhp\Exceptions\EmailEngineException;
use Postalsys\EmailEnginePhp\Resources\Accounts;
use Postalsys\EmailEnginePhp\Resources\Messages;
use Postalsys\EmailEnginePhp\Resources\Mailboxes;
use Postalsys\EmailEnginePhp\Resources\Settings;

class EmailEngineTest extends TestCase
{
    private EmailEngine $client;

    protected function setUp(): void
    {
        $this->client = new EmailEngine(
            accessToken: 'test-token-12345',
            baseUrl: 'http://localhost:3000',
            serviceSecret: 'test-secret',
            redirectUrl: 'http://localhost:5000/callback'
        );
    }

    private function createClientWithMockHandler(array $responses): EmailEngine
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handlerStack]);

        $client = new EmailEngine(
            accessToken: 'test-token',
            baseUrl: 'http://localhost:3000',
            serviceSecret: 'test-secret',
            redirectUrl: 'http://localhost:5000/callback'
        );
        $client->getHttpClient()->setGuzzleClient($guzzle);

        return $client;
    }

    public function testConstructorSetsProperties(): void
    {
        $client = new EmailEngine(
            accessToken: 'my-token',
            baseUrl: 'http://example.com:3000',
            serviceSecret: 'my-secret',
            redirectUrl: 'http://callback.com'
        );

        $this->assertInstanceOf(EmailEngine::class, $client);
    }

    public function testFromOptionsFactoryMethod(): void
    {
        $client = EmailEngine::fromOptions([
            'access_token' => 'my-token',
            'ee_base_url' => 'http://example.com:3000',
            'service_secret' => 'my-secret',
            'redirect_url' => 'http://callback.com',
        ]);

        $this->assertInstanceOf(EmailEngine::class, $client);
    }

    public function testFromOptionsWithDefaultValues(): void
    {
        $client = EmailEngine::fromOptions([
            'access_token' => 'my-token',
        ]);

        $this->assertInstanceOf(EmailEngine::class, $client);
    }

    public function testAccountsResourceAccess(): void
    {
        $this->assertInstanceOf(Accounts::class, $this->client->accounts());
        $this->assertInstanceOf(Accounts::class, $this->client->accounts);
    }

    public function testMessagesResourceAccess(): void
    {
        $this->assertInstanceOf(Messages::class, $this->client->messages());
        $this->assertInstanceOf(Messages::class, $this->client->messages);
    }

    public function testMailboxesResourceAccess(): void
    {
        $this->assertInstanceOf(Mailboxes::class, $this->client->mailboxes());
        $this->assertInstanceOf(Mailboxes::class, $this->client->mailboxes);
    }

    public function testSettingsResourceAccess(): void
    {
        $this->assertInstanceOf(Settings::class, $this->client->settings());
        $this->assertInstanceOf(Settings::class, $this->client->settings);
    }

    public function testResourceInstancesAreCached(): void
    {
        $accounts1 = $this->client->accounts();
        $accounts2 = $this->client->accounts();

        $this->assertSame($accounts1, $accounts2);
    }

    public function testMagicGetThrowsForUnknownProperty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown property: unknownResource');

        $this->client->unknownResource;
    }

    public function testGetAuthenticationUrlCallsApiEndpoint(): void
    {
        $expectedUrl = 'http://localhost:3000/accounts/new?data=eyJhY2NvdW50IjoidGVzdC1hY2NvdW50IiwidCI6MTcwMDAwMDAwMDAwMCwibiI6InRlc3Qtbm9uY2UifQ&sig=test-signature&type=imap';

        $client = $this->createClientWithMockHandler([
            new Response(200, [], json_encode(['url' => $expectedUrl])),
        ]);

        $url = $client->getAuthenticationUrl([
            'account' => 'test-account',
        ]);

        $this->assertEquals($expectedUrl, $url);
    }

    public function testGetAuthenticationUrlUsesDefaultRedirectUrl(): void
    {
        $expectedUrl = 'http://localhost:3000/accounts/new?data=test&sig=test';

        $client = $this->createClientWithMockHandler([
            new Response(200, [], json_encode(['url' => $expectedUrl])),
        ]);

        // The default redirectUrl is configured in createClientWithMockHandler
        // It should be sent to the API
        $url = $client->getAuthenticationUrl([
            'account' => 'test-account',
        ]);

        $this->assertEquals($expectedUrl, $url);
    }

    public function testGetAuthenticationUrlWithAllParameters(): void
    {
        $expectedUrl = 'http://localhost:3000/accounts/new?data=test&sig=test&type=gmail';

        $client = $this->createClientWithMockHandler([
            new Response(200, [], json_encode(['url' => $expectedUrl])),
        ]);

        $url = $client->getAuthenticationUrl([
            'account' => 'test-account',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'redirectUrl' => 'http://custom.com/callback',
            'type' => 'gmail',
            'delegated' => true,
            'syncFrom' => '2024-01-01T00:00:00Z',
            'notifyFrom' => '2024-01-01T00:00:00Z',
            'subconnections' => ['Shared Mailbox'],
            'path' => ['INBOX', 'Sent'],
        ]);

        $this->assertEquals($expectedUrl, $url);
    }

    public function testGetAuthenticationUrlThrowsWithoutRedirectUrl(): void
    {
        $client = new EmailEngine(
            accessToken: 'test-token',
            baseUrl: 'http://localhost:3000'
        );

        $this->expectException(EmailEngineException::class);
        $this->expectExceptionMessage('redirectUrl is required');

        $client->getAuthenticationUrl(['account' => 'test']);
    }

    public function testGetAuthenticationUrlThrowsOnInvalidApiResponse(): void
    {
        $client = $this->createClientWithMockHandler([
            new Response(200, [], json_encode(['status' => 'ok'])), // Missing 'url' field
        ]);

        $this->expectException(EmailEngineException::class);
        $this->expectExceptionMessage('Invalid response from authentication form API');

        $client->getAuthenticationUrl([
            'account' => 'test-account',
        ]);
    }

    public function testBase64EncodeUrlsafe(): void
    {
        $input = 'Hello World! This is a test+/=';
        $encoded = $this->client->base64EncodeUrlsafe($input);

        $this->assertStringNotContainsString('+', $encoded);
        $this->assertStringNotContainsString('/', $encoded);
        $this->assertStringNotContainsString('=', $encoded);
    }

    public function testBase64DecodeUrlsafe(): void
    {
        $original = 'Hello World! This is a test';
        $encoded = $this->client->base64EncodeUrlsafe($original);
        $decoded = $this->client->base64DecodeUrlsafe($encoded);

        $this->assertEquals($original, $decoded);
    }

    public function testSignRequest(): void
    {
        $value = 'test data';
        $secret = 'my-secret';

        $signature = $this->client->signRequest($value, $secret);

        // Verify it's a valid HMAC-SHA256 signature (32 bytes raw)
        $this->assertEquals(32, strlen($signature));

        // Verify it's deterministic
        $signature2 = $this->client->signRequest($value, $secret);
        $this->assertEquals($signature, $signature2);

        // Verify different input produces different signature
        $signature3 = $this->client->signRequest('different data', $secret);
        $this->assertNotEquals($signature, $signature3);
    }

    public function testLegacyMethodAliases(): void
    {
        // Test legacy method names still work
        $encoded = $this->client->base64_encode_urlsafe('test');
        $decoded = $this->client->base64_decode_urlsafe($encoded);
        $this->assertEquals('test', $decoded);

        $signature = $this->client->sign_request('data', 'secret');
        $this->assertEquals(32, strlen($signature));
    }

    public function testLegacyGetAuthenticationUrlAlias(): void
    {
        $expectedUrl = 'http://localhost:3000/accounts/new?data=test&sig=test';

        $client = $this->createClientWithMockHandler([
            new Response(200, [], json_encode(['url' => $expectedUrl])),
        ]);

        $url = $client->get_authentication_url(['account' => 'test']);
        $this->assertEquals($expectedUrl, $url);
    }

    public function testVerifyWebhookSignatureValid(): void
    {
        $body = '{"event":"messageNew","account":"test-account","data":{}}';
        $secret = 'test-secret';

        // Generate a valid signature
        $signature = $this->client->base64EncodeUrlsafe(
            hash_hmac('sha256', $body, $secret, true)
        );

        $this->assertTrue($this->client->verifyWebhookSignature($body, $signature));
    }

    public function testVerifyWebhookSignatureInvalid(): void
    {
        $body = '{"event":"messageNew","account":"test-account","data":{}}';
        $invalidSignature = 'invalid-signature-here';

        $this->assertFalse($this->client->verifyWebhookSignature($body, $invalidSignature));
    }

    public function testVerifyWebhookSignatureDetectsTampering(): void
    {
        $originalBody = '{"event":"messageNew","account":"test-account","data":{}}';
        $tamperedBody = '{"event":"messageNew","account":"hacked-account","data":{}}';
        $secret = 'test-secret';

        // Generate signature for original body
        $signature = $this->client->base64EncodeUrlsafe(
            hash_hmac('sha256', $originalBody, $secret, true)
        );

        // Verify it works for original body
        $this->assertTrue($this->client->verifyWebhookSignature($originalBody, $signature));

        // Verify it fails for tampered body
        $this->assertFalse($this->client->verifyWebhookSignature($tamperedBody, $signature));
    }

    public function testVerifyWebhookSignatureThrowsWithoutServiceSecret(): void
    {
        $client = new EmailEngine(
            accessToken: 'test-token',
            baseUrl: 'http://localhost:3000'
        );

        $this->expectException(EmailEngineException::class);
        $this->expectExceptionMessage('Service secret is required for verifying webhook signatures');

        $client->verifyWebhookSignature('{"test": true}', 'some-signature');
    }

    public function testVerifyWebhookSignatureWithEmptyBody(): void
    {
        $body = '';
        $secret = 'test-secret';

        // Generate a valid signature for empty body
        $signature = $this->client->base64EncodeUrlsafe(
            hash_hmac('sha256', $body, $secret, true)
        );

        $this->assertTrue($this->client->verifyWebhookSignature($body, $signature));
    }

    public function testGetHttpClient(): void
    {
        $httpClient = $this->client->getHttpClient();

        $this->assertInstanceOf(\Postalsys\EmailEnginePhp\HttpClient::class, $httpClient);
    }
}

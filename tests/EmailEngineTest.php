<?php

declare(strict_types=1);

namespace Postalsys\EmailEnginePhp\Tests;

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

    public function testGetAuthenticationUrlGeneratesValidUrl(): void
    {
        $url = $this->client->getAuthenticationUrl([
            'account' => 'test-account',
        ]);

        $this->assertStringStartsWith('http://localhost:3000/accounts/new?data=', $url);
        $this->assertStringContainsString('&sig=', $url);
    }

    public function testGetAuthenticationUrlUsesDefaultRedirectUrl(): void
    {
        $url = $this->client->getAuthenticationUrl([
            'account' => 'test-account',
        ]);

        // Parse the URL and decode the data parameter
        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        $data = json_decode($this->client->base64DecodeUrlsafe($params['data']), true);

        $this->assertEquals('http://localhost:5000/callback', $data['redirectUrl']);
    }

    public function testGetAuthenticationUrlWithCustomRedirectUrl(): void
    {
        $url = $this->client->getAuthenticationUrl([
            'account' => 'test-account',
            'redirectUrl' => 'http://custom.com/callback',
        ]);

        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        $data = json_decode($this->client->base64DecodeUrlsafe($params['data']), true);

        $this->assertEquals('http://custom.com/callback', $data['redirectUrl']);
    }

    public function testGetAuthenticationUrlThrowsWithoutServiceSecret(): void
    {
        $client = new EmailEngine(
            accessToken: 'test-token',
            baseUrl: 'http://localhost:3000'
        );

        $this->expectException(EmailEngineException::class);
        $this->expectExceptionMessage('Service secret is required');

        $client->getAuthenticationUrl(['account' => 'test']);
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

        $url = $this->client->get_authentication_url(['account' => 'test']);
        $this->assertStringStartsWith('http://localhost:3000/accounts/new', $url);
    }

    public function testGetHttpClient(): void
    {
        $httpClient = $this->client->getHttpClient();

        $this->assertInstanceOf(\Postalsys\EmailEnginePhp\HttpClient::class, $httpClient);
    }
}

<?php

declare(strict_types=1);

namespace Postalsys\EmailEnginePhp\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Postalsys\EmailEnginePhp\HttpClient;
use Postalsys\EmailEnginePhp\Exceptions\AuthenticationException;
use Postalsys\EmailEnginePhp\Exceptions\AuthorizationException;
use Postalsys\EmailEnginePhp\Exceptions\EmailEngineException;
use Postalsys\EmailEnginePhp\Exceptions\NotFoundException;
use Postalsys\EmailEnginePhp\Exceptions\RateLimitException;
use Postalsys\EmailEnginePhp\Exceptions\ServerException;
use Postalsys\EmailEnginePhp\Exceptions\ValidationException;

class HttpClientTest extends TestCase
{
    private function createClientWithMockHandler(array $responses): HttpClient
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handlerStack]);

        $client = new HttpClient(
            baseUrl: 'http://localhost:3000',
            accessToken: 'test-token'
        );
        $client->setGuzzleClient($guzzle);

        return $client;
    }

    public function testGetRequest(): void
    {
        $client = $this->createClientWithMockHandler([
            new Response(200, [], json_encode(['accounts' => []])),
        ]);

        $result = $client->get('/v1/accounts');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('accounts', $result);
    }

    public function testGetRequestWithQueryParams(): void
    {
        $client = $this->createClientWithMockHandler([
            new Response(200, [], json_encode(['accounts' => [], 'total' => 10])),
        ]);

        $result = $client->get('/v1/accounts', ['page' => 1, 'pageSize' => 20]);

        $this->assertIsArray($result);
        $this->assertEquals(10, $result['total']);
    }

    public function testPostRequest(): void
    {
        $client = $this->createClientWithMockHandler([
            new Response(200, [], json_encode(['account' => 'test-account', 'state' => 'init'])),
        ]);

        $result = $client->post('/v1/account', [
            'account' => 'test-account',
            'name' => 'Test Account',
        ]);

        $this->assertIsArray($result);
        $this->assertEquals('test-account', $result['account']);
        $this->assertEquals('init', $result['state']);
    }

    public function testPutRequest(): void
    {
        $client = $this->createClientWithMockHandler([
            new Response(200, [], json_encode(['account' => 'test-account'])),
        ]);

        $result = $client->put('/v1/account/test-account', [
            'name' => 'Updated Name',
        ]);

        $this->assertIsArray($result);
        $this->assertEquals('test-account', $result['account']);
    }

    public function testDeleteRequest(): void
    {
        $client = $this->createClientWithMockHandler([
            new Response(200, [], json_encode(['account' => 'test-account', 'deleted' => true])),
        ]);

        $result = $client->delete('/v1/account/test-account');

        $this->assertIsArray($result);
        $this->assertTrue($result['deleted']);
    }

    public function testEmptyResponse(): void
    {
        $client = $this->createClientWithMockHandler([
            new Response(200, [], ''),
        ]);

        $result = $client->get('/v1/some-endpoint');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testAuthenticationException(): void
    {
        $mock = new MockHandler([
            new RequestException(
                'Unauthorized',
                new Request('GET', '/v1/accounts'),
                new Response(401, [], json_encode([
                    'error' => 'Invalid access token',
                    'code' => 'INVALID_TOKEN',
                ]))
            ),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handlerStack]);

        $client = new HttpClient('http://localhost:3000', 'invalid-token');
        $client->setGuzzleClient($guzzle);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid access token');

        $client->get('/v1/accounts');
    }

    public function testAuthorizationException(): void
    {
        $mock = new MockHandler([
            new RequestException(
                'Forbidden',
                new Request('GET', '/v1/accounts'),
                new Response(403, [], json_encode([
                    'error' => 'Access denied',
                    'code' => 'ACCESS_DENIED',
                ]))
            ),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handlerStack]);

        $client = new HttpClient('http://localhost:3000', 'test-token');
        $client->setGuzzleClient($guzzle);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Access denied');

        $client->get('/v1/accounts');
    }

    public function testNotFoundException(): void
    {
        $mock = new MockHandler([
            new RequestException(
                'Not Found',
                new Request('GET', '/v1/account/unknown'),
                new Response(404, [], json_encode([
                    'error' => 'Account not found',
                    'code' => 'ACCOUNT_NOT_FOUND',
                ]))
            ),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handlerStack]);

        $client = new HttpClient('http://localhost:3000', 'test-token');
        $client->setGuzzleClient($guzzle);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Account not found');

        $client->get('/v1/account/unknown');
    }

    public function testValidationException(): void
    {
        $mock = new MockHandler([
            new RequestException(
                'Bad Request',
                new Request('POST', '/v1/account'),
                new Response(400, [], json_encode([
                    'error' => 'Validation failed',
                    'code' => 'VALIDATION_ERROR',
                    'details' => ['field' => 'email', 'message' => 'Invalid email format'],
                ]))
            ),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handlerStack]);

        $client = new HttpClient('http://localhost:3000', 'test-token');
        $client->setGuzzleClient($guzzle);

        try {
            $client->post('/v1/account', ['email' => 'invalid']);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('Validation failed', $e->getMessage());
            $this->assertEquals('VALIDATION_ERROR', $e->getErrorCode());
            $this->assertIsArray($e->getDetails());
        }
    }

    public function testRateLimitException(): void
    {
        $mock = new MockHandler([
            new RequestException(
                'Too Many Requests',
                new Request('GET', '/v1/accounts'),
                new Response(429, ['Retry-After' => '60'], json_encode([
                    'error' => 'Rate limit exceeded',
                    'code' => 'RATE_LIMIT',
                ]))
            ),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handlerStack]);

        $client = new HttpClient('http://localhost:3000', 'test-token');
        $client->setGuzzleClient($guzzle);

        try {
            $client->get('/v1/accounts');
            $this->fail('Expected RateLimitException');
        } catch (RateLimitException $e) {
            $this->assertEquals('Rate limit exceeded', $e->getMessage());
            $this->assertEquals(60, $e->getRetryAfter());
        }
    }

    public function testServerException(): void
    {
        $mock = new MockHandler([
            new RequestException(
                'Internal Server Error',
                new Request('GET', '/v1/accounts'),
                new Response(500, [], json_encode([
                    'error' => 'Internal server error',
                    'code' => 'INTERNAL_ERROR',
                ]))
            ),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handlerStack]);

        $client = new HttpClient('http://localhost:3000', 'test-token');
        $client->setGuzzleClient($guzzle);

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Internal server error');

        $client->get('/v1/accounts');
    }

    public function testGenericExceptionForUnknownStatusCode(): void
    {
        $mock = new MockHandler([
            new RequestException(
                'I\'m a teapot',
                new Request('GET', '/v1/accounts'),
                new Response(418, [], json_encode([
                    'error' => 'I\'m a teapot',
                ]))
            ),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handlerStack]);

        $client = new HttpClient('http://localhost:3000', 'test-token');
        $client->setGuzzleClient($guzzle);

        $this->expectException(EmailEngineException::class);

        $client->get('/v1/accounts');
    }

    public function testRequestWithCustomHeaders(): void
    {
        $client = $this->createClientWithMockHandler([
            new Response(200, [], json_encode(['success' => true])),
        ]);

        $result = $client->request(
            'POST',
            '/v1/account/test/submit',
            data: ['subject' => 'Test'],
            headers: [
                'Idempotency-Key' => 'unique-key-123',
                'X-EE-Timeout' => '60000',
            ]
        );

        $this->assertTrue($result['success']);
    }

    public function testGetGuzzleClient(): void
    {
        $client = new HttpClient('http://localhost:3000', 'test-token');

        $this->assertInstanceOf(Client::class, $client->getGuzzleClient());
    }

    public function testInvalidJsonResponse(): void
    {
        $client = $this->createClientWithMockHandler([
            new Response(200, [], 'invalid json {{{'),
        ]);

        $this->expectException(EmailEngineException::class);
        $this->expectExceptionMessage('Failed to parse JSON response');

        $client->get('/v1/accounts');
    }
}

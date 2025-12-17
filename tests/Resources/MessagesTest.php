<?php

declare(strict_types=1);

namespace Postalsys\EmailEnginePhp\Tests\Resources;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Postalsys\EmailEnginePhp\HttpClient;
use Postalsys\EmailEnginePhp\Resources\Messages;

class MessagesTest extends TestCase
{
    private function createMessagesWithMockHandler(array $responses): Messages
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handlerStack]);

        $httpClient = new HttpClient(
            baseUrl: 'http://localhost:3000',
            accessToken: 'test-token'
        );
        $httpClient->setGuzzleClient($guzzle);

        return new Messages($httpClient);
    }

    public function testListMessages(): void
    {
        $messages = $this->createMessagesWithMockHandler([
            new Response(200, [], json_encode([
                'messages' => [
                    ['id' => 'msg-1', 'subject' => 'Test 1'],
                    ['id' => 'msg-2', 'subject' => 'Test 2'],
                ],
                'total' => 2,
                'page' => 0,
            ])),
        ]);

        $result = $messages->list('test-account', ['path' => 'INBOX']);

        $this->assertCount(2, $result['messages']);
        $this->assertEquals(2, $result['total']);
    }

    public function testGetMessage(): void
    {
        $messages = $this->createMessagesWithMockHandler([
            new Response(200, [], json_encode([
                'id' => 'msg-1',
                'subject' => 'Test Subject',
                'from' => [['name' => 'Sender', 'address' => 'sender@example.com']],
                'to' => [['name' => 'Recipient', 'address' => 'recipient@example.com']],
                'date' => '2024-01-15T10:30:00Z',
                'flags' => ['\\Seen'],
            ])),
        ]);

        $result = $messages->get('test-account', 'msg-1');

        $this->assertEquals('msg-1', $result['id']);
        $this->assertEquals('Test Subject', $result['subject']);
        $this->assertContains('\\Seen', $result['flags']);
    }

    public function testGetMessageSource(): void
    {
        $rawSource = "From: sender@example.com\r\nTo: recipient@example.com\r\nSubject: Test\r\n\r\nBody";

        $messages = $this->createMessagesWithMockHandler([
            new Response(200, [], json_encode([
                'source' => $rawSource,
            ])),
        ]);

        $result = $messages->getSource('test-account', 'msg-1');

        $this->assertEquals($rawSource, $result['source']);
    }

    public function testGetText(): void
    {
        $messages = $this->createMessagesWithMockHandler([
            new Response(200, [], json_encode([
                'plain' => 'Plain text content',
                'html' => '<p>HTML content</p>',
            ])),
        ]);

        $result = $messages->getText('test-account', 'text-id-123');

        $this->assertEquals('Plain text content', $result['plain']);
        $this->assertEquals('<p>HTML content</p>', $result['html']);
    }

    public function testUpdateMessageFlags(): void
    {
        $messages = $this->createMessagesWithMockHandler([
            new Response(200, [], json_encode([
                'flags' => ['\\Seen', '\\Flagged'],
            ])),
        ]);

        $result = $messages->update('test-account', 'msg-1', [
            'flags' => ['add' => ['\\Seen', '\\Flagged']],
        ]);

        $this->assertContains('\\Seen', $result['flags']);
        $this->assertContains('\\Flagged', $result['flags']);
    }

    public function testMoveMessage(): void
    {
        $messages = $this->createMessagesWithMockHandler([
            new Response(200, [], json_encode([
                'path' => 'Archive',
                'id' => 'msg-1-new',
                'uid' => 12345,
            ])),
        ]);

        $result = $messages->move('test-account', 'msg-1', 'Archive');

        $this->assertEquals('Archive', $result['path']);
    }

    public function testDeleteMessage(): void
    {
        $messages = $this->createMessagesWithMockHandler([
            new Response(200, [], json_encode([
                'deleted' => true,
            ])),
        ]);

        $result = $messages->delete('test-account', 'msg-1');

        $this->assertTrue($result['deleted']);
    }

    public function testDeleteMessageForce(): void
    {
        $messages = $this->createMessagesWithMockHandler([
            new Response(200, [], json_encode([
                'deleted' => true,
            ])),
        ]);

        $result = $messages->delete('test-account', 'msg-1', force: true);

        $this->assertTrue($result['deleted']);
    }

    public function testBulkUpdateMessages(): void
    {
        $messages = $this->createMessagesWithMockHandler([
            new Response(200, [], json_encode([
                'path' => 'INBOX',
                'updated' => 5,
            ])),
        ]);

        $result = $messages->bulkUpdate('test-account', [
            'path' => 'INBOX',
            'messages' => ['msg-1', 'msg-2', 'msg-3', 'msg-4', 'msg-5'],
            'flags' => ['add' => ['\\Seen']],
        ]);

        $this->assertEquals(5, $result['updated']);
    }

    public function testBulkMoveMessages(): void
    {
        $messages = $this->createMessagesWithMockHandler([
            new Response(200, [], json_encode([
                'path' => 'INBOX',
                'destination' => 'Archive',
                'moved' => 3,
            ])),
        ]);

        $result = $messages->bulkMove('test-account', [
            'path' => 'INBOX',
            'destination' => 'Archive',
            'messages' => ['msg-1', 'msg-2', 'msg-3'],
        ]);

        $this->assertEquals(3, $result['moved']);
    }

    public function testBulkDeleteMessages(): void
    {
        $messages = $this->createMessagesWithMockHandler([
            new Response(200, [], json_encode([
                'path' => 'INBOX',
                'deleted' => 2,
            ])),
        ]);

        $result = $messages->bulkDelete('test-account', [
            'path' => 'INBOX',
            'messages' => ['msg-1', 'msg-2'],
        ]);

        $this->assertEquals(2, $result['deleted']);
    }

    public function testSearchMessages(): void
    {
        $messages = $this->createMessagesWithMockHandler([
            new Response(200, [], json_encode([
                'messages' => [
                    ['id' => 'msg-1', 'subject' => 'Important'],
                ],
                'total' => 1,
            ])),
        ]);

        $result = $messages->search('test-account', [
            'path' => 'INBOX',
            'search' => [
                'unseen' => true,
                'subject' => 'Important',
            ],
        ]);

        $this->assertCount(1, $result['messages']);
    }

    public function testUnifiedSearch(): void
    {
        $messages = $this->createMessagesWithMockHandler([
            new Response(200, [], json_encode([
                'messages' => [
                    ['id' => 'msg-1', 'account' => 'account-1'],
                    ['id' => 'msg-2', 'account' => 'account-2'],
                ],
                'total' => 2,
            ])),
        ]);

        $result = $messages->unifiedSearch([
            'accounts' => ['account-1', 'account-2'],
            'query' => 'test query',
        ]);

        $this->assertCount(2, $result['messages']);
    }

    public function testCreateDraft(): void
    {
        $messages = $this->createMessagesWithMockHandler([
            new Response(200, [], json_encode([
                'id' => 'draft-1',
                'uid' => 100,
                'path' => 'Drafts',
            ])),
        ]);

        $result = $messages->create('test-account', [
            'path' => 'Drafts',
            'from' => ['name' => 'Sender', 'address' => 'sender@example.com'],
            'to' => [['name' => 'Recipient', 'address' => 'recipient@example.com']],
            'subject' => 'Draft Email',
            'text' => 'Draft content',
        ]);

        $this->assertEquals('draft-1', $result['id']);
        $this->assertEquals('Drafts', $result['path']);
    }

    public function testSubmitMessage(): void
    {
        $messages = $this->createMessagesWithMockHandler([
            new Response(200, [], json_encode([
                'response' => 'Queued',
                'messageId' => '<msg-123@example.com>',
                'queueId' => 'queue-456',
            ])),
        ]);

        $result = $messages->submit('test-account', [
            'from' => ['name' => 'Sender', 'address' => 'sender@example.com'],
            'to' => [['name' => 'Recipient', 'address' => 'recipient@example.com']],
            'subject' => 'Test Email',
            'text' => 'Hello World!',
            'html' => '<p>Hello World!</p>',
        ]);

        $this->assertEquals('Queued', $result['response']);
        $this->assertEquals('<msg-123@example.com>', $result['messageId']);
    }

    public function testSubmitMessageWithIdempotencyKey(): void
    {
        $messages = $this->createMessagesWithMockHandler([
            new Response(200, [], json_encode([
                'response' => 'Queued',
                'messageId' => '<msg-123@example.com>',
            ])),
        ]);

        $result = $messages->submit('test-account', [
            'from' => ['name' => 'Sender', 'address' => 'sender@example.com'],
            'to' => [['name' => 'Recipient', 'address' => 'recipient@example.com']],
            'subject' => 'Test Email',
            'text' => 'Hello World!',
        ], [
            'idempotencyKey' => 'unique-key-12345',
        ]);

        $this->assertEquals('Queued', $result['response']);
    }

    public function testSubmitMessageWithTemplate(): void
    {
        $messages = $this->createMessagesWithMockHandler([
            new Response(200, [], json_encode([
                'response' => 'Queued',
                'messageId' => '<msg-123@example.com>',
            ])),
        ]);

        $result = $messages->submit('test-account', [
            'to' => [['name' => 'John', 'address' => 'john@example.com']],
            'template' => 'welcome-template',
            'render' => [
                'name' => 'John',
                'company' => 'Acme Inc',
            ],
        ]);

        $this->assertEquals('Queued', $result['response']);
    }

    public function testGetAttachment(): void
    {
        $messages = $this->createMessagesWithMockHandler([
            new Response(200, [], json_encode([
                'content' => base64_encode('file content'),
                'contentType' => 'application/pdf',
                'filename' => 'document.pdf',
            ])),
        ]);

        $result = $messages->getAttachment('test-account', 'attachment-id-123');

        $this->assertEquals('application/pdf', $result['contentType']);
        $this->assertEquals('document.pdf', $result['filename']);
    }
}

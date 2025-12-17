<?php

declare(strict_types=1);

namespace Postalsys\EmailEnginePhp\Tests\Resources;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Postalsys\EmailEnginePhp\HttpClient;
use Postalsys\EmailEnginePhp\Resources\Mailboxes;

class MailboxesTest extends TestCase
{
    private function createMailboxesWithMockHandler(array $responses): Mailboxes
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handlerStack]);

        $httpClient = new HttpClient(
            baseUrl: 'http://localhost:3000',
            accessToken: 'test-token'
        );
        $httpClient->setGuzzleClient($guzzle);

        return new Mailboxes($httpClient);
    }

    public function testListMailboxes(): void
    {
        $mailboxes = $this->createMailboxesWithMockHandler([
            new Response(200, [], json_encode([
                'mailboxes' => [
                    [
                        'path' => 'INBOX',
                        'name' => 'Inbox',
                        'delimiter' => '/',
                        'listed' => true,
                        'messages' => 100,
                        'uidNext' => 200,
                    ],
                    [
                        'path' => 'Sent',
                        'name' => 'Sent',
                        'specialUse' => '\\Sent',
                        'messages' => 50,
                    ],
                    [
                        'path' => 'Drafts',
                        'name' => 'Drafts',
                        'specialUse' => '\\Drafts',
                        'messages' => 5,
                    ],
                ],
            ])),
        ]);

        $result = $mailboxes->list('test-account');

        $this->assertCount(3, $result['mailboxes']);
        $this->assertEquals('INBOX', $result['mailboxes'][0]['path']);
        $this->assertEquals(100, $result['mailboxes'][0]['messages']);
    }

    public function testListMailboxesWithCounters(): void
    {
        $mailboxes = $this->createMailboxesWithMockHandler([
            new Response(200, [], json_encode([
                'mailboxes' => [
                    [
                        'path' => 'INBOX',
                        'name' => 'Inbox',
                        'messages' => 100,
                        'unseen' => 10,
                    ],
                ],
            ])),
        ]);

        $result = $mailboxes->list('test-account', ['counters' => true]);

        $this->assertCount(1, $result['mailboxes']);
    }

    public function testCreateMailbox(): void
    {
        $mailboxes = $this->createMailboxesWithMockHandler([
            new Response(200, [], json_encode([
                'path' => 'INBOX/Projects',
                'created' => true,
            ])),
        ]);

        $result = $mailboxes->create('test-account', 'INBOX/Projects');

        $this->assertEquals('INBOX/Projects', $result['path']);
        $this->assertTrue($result['created']);
    }

    public function testRenameMailbox(): void
    {
        $mailboxes = $this->createMailboxesWithMockHandler([
            new Response(200, [], json_encode([
                'path' => 'INBOX/OldFolder',
                'newPath' => 'INBOX/NewFolder',
                'renamed' => true,
            ])),
        ]);

        $result = $mailboxes->rename('test-account', 'INBOX/OldFolder', 'INBOX/NewFolder');

        $this->assertEquals('INBOX/OldFolder', $result['path']);
        $this->assertEquals('INBOX/NewFolder', $result['newPath']);
        $this->assertTrue($result['renamed']);
    }

    public function testDeleteMailbox(): void
    {
        $mailboxes = $this->createMailboxesWithMockHandler([
            new Response(200, [], json_encode([
                'path' => 'INBOX/ToDelete',
                'deleted' => true,
            ])),
        ]);

        $result = $mailboxes->delete('test-account', 'INBOX/ToDelete');

        $this->assertEquals('INBOX/ToDelete', $result['path']);
        $this->assertTrue($result['deleted']);
    }

    public function testSubscribeMailbox(): void
    {
        $mailboxes = $this->createMailboxesWithMockHandler([
            new Response(200, [], json_encode([
                'path' => 'Archive',
                'subscribed' => true,
            ])),
        ]);

        $result = $mailboxes->subscribe('test-account', 'Archive');

        $this->assertEquals('Archive', $result['path']);
        $this->assertTrue($result['subscribed']);
    }

    public function testUnsubscribeMailbox(): void
    {
        $mailboxes = $this->createMailboxesWithMockHandler([
            new Response(200, [], json_encode([
                'path' => 'Archive',
                'subscribed' => false,
            ])),
        ]);

        $result = $mailboxes->unsubscribe('test-account', 'Archive');

        $this->assertEquals('Archive', $result['path']);
        $this->assertFalse($result['subscribed']);
    }
}

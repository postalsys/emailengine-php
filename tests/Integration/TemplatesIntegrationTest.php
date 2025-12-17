<?php

declare(strict_types=1);

namespace Postalsys\EmailEnginePhp\Tests\Integration;

use Postalsys\EmailEnginePhp\Exceptions\NotFoundException;

/**
 * Integration tests for Templates resource
 *
 * Tests email template management against a real EmailEngine instance.
 */
class TemplatesIntegrationTest extends IntegrationTestCase
{
    private static array $createdTemplateIds = [];

    public static function tearDownAfterClass(): void
    {
        // Clean up any templates created during tests
        if (self::$client !== null) {
            foreach (self::$createdTemplateIds as $templateId) {
                try {
                    self::$client->templates->delete($templateId);
                } catch (\Exception $e) {
                    // Ignore cleanup errors
                }
            }
        }

        parent::tearDownAfterClass();
    }

    public function testListTemplates(): void
    {
        $result = $this->getClient()->templates->list();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('templates', $result);
        $this->assertIsArray($result['templates']);
    }

    public function testCreateTemplate(): void
    {
        $templateId = $this->generateTestId('template');

        $result = $this->getClient()->templates->createOrUpdate($templateId, [
            'name' => 'Test Template',
            'description' => 'A test template for integration testing',
            'format' => 'html',
            'content' => [
                'subject' => 'Hello {{name}}!',
                'text' => 'Hello {{name}}, welcome to our service.',
                'html' => '<h1>Hello {{name}}!</h1><p>Welcome to our service.</p>',
            ],
        ]);

        self::$createdTemplateIds[] = $templateId;

        $this->assertIsArray($result);
        // API may return the template with 'id' or 'template' key
        $returnedId = $result['id'] ?? $result['template'] ?? null;
        $this->assertNotNull($returnedId, 'Response should contain template ID');
        // Store the actual returned ID for subsequent tests
        if ($returnedId !== $templateId) {
            self::$createdTemplateIds[array_key_last(self::$createdTemplateIds)] = $returnedId;
        }
    }

    /**
     * @depends testCreateTemplate
     */
    public function testGetTemplate(): void
    {
        if (empty(self::$createdTemplateIds)) {
            $this->markTestSkipped('No test template available');
        }

        $templateId = self::$createdTemplateIds[0];
        $result = $this->getClient()->templates->get($templateId);

        $this->assertIsArray($result);
        $returnedId = $result['id'] ?? $result['template'] ?? null;
        $this->assertEquals($templateId, $returnedId);
        $this->assertEquals('Test Template', $result['name']);
        $this->assertArrayHasKey('content', $result);
    }

    /**
     * @depends testGetTemplate
     */
    public function testUpdateTemplate(): void
    {
        if (empty(self::$createdTemplateIds)) {
            $this->markTestSkipped('No test template available');
        }

        $templateId = self::$createdTemplateIds[0];
        $newName = 'Updated Test Template ' . time();

        $result = $this->getClient()->templates->update($templateId, [
            'name' => $newName,
        ]);

        $this->assertIsArray($result);

        // Verify the update
        $template = $this->getClient()->templates->get($templateId);
        $this->assertEquals($newName, $template['name']);
    }

    public function testGetNonExistentTemplate(): void
    {
        $this->expectException(NotFoundException::class);

        $this->getClient()->templates->get('non-existent-template-12345');
    }

    /**
     * @depends testUpdateTemplate
     */
    public function testDeleteTemplate(): void
    {
        if (empty(self::$createdTemplateIds)) {
            $this->markTestSkipped('No test template available');
        }

        $templateId = array_pop(self::$createdTemplateIds);
        $result = $this->getClient()->templates->delete($templateId);

        $this->assertIsArray($result);
        $this->assertTrue($result['deleted']);

        // Verify deletion
        $this->expectException(NotFoundException::class);
        $this->getClient()->templates->get($templateId);
    }
}

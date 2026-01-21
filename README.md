# EmailEngine PHP SDK

[![Tests](https://github.com/postalsys/emailengine-php/actions/workflows/tests.yml/badge.svg)](https://github.com/postalsys/emailengine-php/actions/workflows/tests.yml)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Modern PHP SDK for [EmailEngine](https://emailengine.app/) - the self-hosted email gateway that provides a REST API for IMAP and SMTP operations.

## Requirements

- PHP 8.1 or higher
- Composer
- EmailEngine instance

## Installation

```bash
composer require postalsys/emailengine-php
```

## Quick Start

```php
use Postalsys\EmailEnginePhp\EmailEngine;

$client = new EmailEngine(
    accessToken: 'your-access-token',
    baseUrl: 'http://localhost:3000',
);

// List all accounts
$accounts = $client->accounts->list();

// Send an email
$result = $client->messages->submit('account-id', [
    'from' => ['name' => 'Sender', 'address' => 'sender@example.com'],
    'to' => [['name' => 'Recipient', 'address' => 'recipient@example.com']],
    'subject' => 'Hello from EmailEngine PHP SDK',
    'text' => 'This is a test email.',
    'html' => '<p>This is a test email.</p>',
]);
```

## Configuration

### Constructor Parameters

```php
$client = new EmailEngine(
    accessToken: 'your-access-token',      // Required: API access token
    baseUrl: 'http://localhost:3000',       // EmailEngine base URL (default: localhost:3000)
    serviceSecret: 'your-service-secret',   // For verifying webhook signatures
    redirectUrl: 'http://your-app/callback', // Default redirect URL for hosted auth
    timeout: 30,                            // Request timeout in seconds
);
```

### Factory Method (Legacy Compatibility)

```php
$client = EmailEngine::fromOptions([
    'access_token' => 'your-access-token',
    'ee_base_url' => 'http://localhost:3000',
    'service_secret' => 'your-service-secret',
    'redirect_url' => 'http://your-app/callback',
]);
```

## Resources

The SDK provides access to all EmailEngine API endpoints through resource classes:

| Resource | Description |
|----------|-------------|
| `$client->accounts` | Account management (CRUD, sync, reconnect) |
| `$client->messages` | Message operations (list, read, send, search) |
| `$client->mailboxes` | Mailbox management (create, rename, delete) |
| `$client->outbox` | Queued message management |
| `$client->settings` | System and webhook settings |
| `$client->tokens` | Access token management |
| `$client->templates` | Email template management |
| `$client->gateways` | SMTP gateway configuration |
| `$client->oauth2` | OAuth2 application management |
| `$client->webhooks` | Webhook route management |
| `$client->stats` | System statistics and utilities |
| `$client->blocklists` | Blocklist management |

## Examples

### Account Management

```php
// Create a new account
$account = $client->accounts->create([
    'account' => 'my-account',
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'imap' => [
        'host' => 'imap.example.com',
        'port' => 993,
        'secure' => true,
        'auth' => ['user' => 'john@example.com', 'pass' => 'password'],
    ],
    'smtp' => [
        'host' => 'smtp.example.com',
        'port' => 465,
        'secure' => true,
        'auth' => ['user' => 'john@example.com', 'pass' => 'password'],
    ],
]);

// Get account info
$info = $client->accounts->get('my-account');
echo "Account state: " . $info['state'];

// List all accounts
$accounts = $client->accounts->list(['page' => 0, 'pageSize' => 20]);

// Force reconnection
$client->accounts->reconnect('my-account');

// Delete account
$client->accounts->delete('my-account');
```

### Messages

```php
// List messages in INBOX
$messages = $client->messages->list('my-account', [
    'path' => 'INBOX',
    'pageSize' => 50,
]);

// Get message details
$message = $client->messages->get('my-account', 'message-id', [
    'textType' => 'html',
]);

// Search messages
$results = $client->messages->search('my-account', [
    'path' => 'INBOX',
    'search' => [
        'unseen' => true,
        'from' => 'important@example.com',
    ],
]);

// Update message flags
$client->messages->update('my-account', 'message-id', [
    'flags' => ['add' => ['\\Seen', '\\Flagged']],
]);

// Move message
$client->messages->move('my-account', 'message-id', 'Archive');

// Delete message
$client->messages->delete('my-account', 'message-id');

// Bulk operations
$client->messages->bulkUpdate('my-account', [
    'path' => 'INBOX',
    'messages' => ['msg-1', 'msg-2', 'msg-3'],
    'flags' => ['add' => ['\\Seen']],
]);
```

### Sending Emails

```php
// Basic email
$result = $client->messages->submit('my-account', [
    'from' => ['name' => 'Sender', 'address' => 'sender@example.com'],
    'to' => [['name' => 'Recipient', 'address' => 'recipient@example.com']],
    'cc' => [['address' => 'cc@example.com']],
    'subject' => 'Test Subject',
    'text' => 'Plain text content',
    'html' => '<p>HTML content</p>',
]);

// With attachments
$result = $client->messages->submit('my-account', [
    'from' => ['address' => 'sender@example.com'],
    'to' => [['address' => 'recipient@example.com']],
    'subject' => 'Email with attachment',
    'text' => 'Please see attached.',
    'attachments' => [
        [
            'filename' => 'document.pdf',
            'content' => base64_encode(file_get_contents('document.pdf')),
            'contentType' => 'application/pdf',
        ],
    ],
]);

// Using templates
$result = $client->messages->submit('my-account', [
    'to' => [['name' => 'John', 'address' => 'john@example.com']],
    'template' => 'welcome-email',
    'render' => [
        'name' => 'John',
        'company' => 'Acme Inc',
    ],
]);

// With idempotency key (prevents duplicates)
$result = $client->messages->submit('my-account', [
    'to' => [['address' => 'recipient@example.com']],
    'subject' => 'Important email',
    'text' => 'Content',
], [
    'idempotencyKey' => 'unique-key-12345',
]);

// Scheduled send
$result = $client->messages->submit('my-account', [
    'to' => [['address' => 'recipient@example.com']],
    'subject' => 'Scheduled email',
    'text' => 'This will be sent later',
    'sendAt' => '2024-12-25T10:00:00Z',
]);
```

### Download Attachments

```php
// Download and stream an attachment directly to the browser
$client->download("/v1/account/my-account/attachment/AAAAAQAABRQ");
```

### Mailbox Management

```php
// List mailboxes
$mailboxes = $client->mailboxes->list('my-account', ['counters' => true]);

// Create mailbox
$client->mailboxes->create('my-account', 'INBOX/Projects');

// Rename mailbox
$client->mailboxes->rename('my-account', 'INBOX/OldName', 'INBOX/NewName');

// Delete mailbox
$client->mailboxes->delete('my-account', 'INBOX/ToDelete');

// Subscribe/unsubscribe
$client->mailboxes->subscribe('my-account', 'Archive');
$client->mailboxes->unsubscribe('my-account', 'Spam');
```

### Webhook Settings

```php
// Get webhook settings
$webhooks = $client->settings->getWebhooks();

// Configure webhooks
$client->settings->setWebhooks([
    'enabled' => true,
    'url' => 'https://your-app.com/webhooks',
    'events' => ['messageNew', 'messageUpdated', 'messageSent'],
    'headers' => ['Received', 'List-ID'],
    'text' => 2048, // Include first 2KB of text content
]);
```

### Hosted Authentication

Generate URLs for EmailEngine's hosted authentication form. This method calls the EmailEngine API to generate a secure authentication URL with server-side nonce and timestamp for replay attack protection.

```php
$client = new EmailEngine(
    accessToken: 'your-token',
    baseUrl: 'http://localhost:3000',
    redirectUrl: 'http://your-app/auth-callback',
);

// Generate auth URL with basic options
$authUrl = $client->getAuthenticationUrl([
    'account' => null, // null = auto-generate account ID
    'name' => 'User Name',
    'email' => 'user@example.com',
]);

// Redirect user to $authUrl
header('Location: ' . $authUrl);
```

#### Available Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `account` | string\|null | Account ID (null to auto-generate) |
| `name` | string | Display name for the account |
| `email` | string | Email address hint |
| `redirectUrl` | string | Override default redirect URL |
| `type` | string | Account type (e.g., 'imap', 'gmail', 'outlook') |
| `delegated` | bool | Enable delegated access |
| `syncFrom` | string | ISO 8601 date to sync messages from |
| `notifyFrom` | string | ISO 8601 date to send notifications from |
| `subconnections` | array | List of shared mailboxes to connect |
| `path` | string\|array | Mailbox path(s) to monitor |

```php
// Example with all parameters
$authUrl = $client->getAuthenticationUrl([
    'account' => 'user-123',
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'type' => 'gmail',
    'delegated' => true,
    'syncFrom' => '2024-01-01T00:00:00Z',
    'notifyFrom' => '2024-01-01T00:00:00Z',
    'subconnections' => ['Shared Mailbox'],
    'path' => ['INBOX', 'Sent'],
]);
```

### Webhook Signature Verification

Verify webhook signatures to ensure requests are authentically from EmailEngine. Requires the `serviceSecret` to be configured.

```php
$client = new EmailEngine(
    accessToken: 'your-token',
    baseUrl: 'http://localhost:3000',
    serviceSecret: 'your-service-secret',
);

// Get the raw request body and signature header
$body = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_EE_WH_SIGNATURE'] ?? '';

if ($client->verifyWebhookSignature($body, $signature)) {
    // Signature is valid - process the webhook
    $payload = json_decode($body, true);
    // ... handle webhook event
} else {
    // Invalid signature - reject the request
    http_response_code(401);
    exit('Invalid signature');
}
```

### Outbox Management

```php
// List queued messages
$queue = $client->outbox->list(['account' => 'my-account']);

// Get queued message details
$item = $client->outbox->get('queue-id');

// Cancel scheduled message
$client->outbox->cancel('queue-id');
```

### System Statistics

```php
// Get system stats
$stats = $client->stats->get();
echo "EmailEngine version: " . $stats['version'];
echo "Connected accounts: " . $stats['connections']['connected'];

// Auto-discover email settings
$config = $client->stats->autoconfig('user@gmail.com');
```

## Error Handling

The SDK throws specific exceptions for different error types:

```php
use Postalsys\EmailEnginePhp\Exceptions\AuthenticationException;
use Postalsys\EmailEnginePhp\Exceptions\AuthorizationException;
use Postalsys\EmailEnginePhp\Exceptions\NotFoundException;
use Postalsys\EmailEnginePhp\Exceptions\ValidationException;
use Postalsys\EmailEnginePhp\Exceptions\RateLimitException;
use Postalsys\EmailEnginePhp\Exceptions\ServerException;
use Postalsys\EmailEnginePhp\Exceptions\EmailEngineException;

try {
    $account = $client->accounts->get('unknown-account');
} catch (NotFoundException $e) {
    echo "Account not found: " . $e->getMessage();
    echo "Error code: " . $e->getErrorCode();
} catch (AuthenticationException $e) {
    echo "Invalid API token";
} catch (ValidationException $e) {
    echo "Validation error: " . $e->getMessage();
    print_r($e->getDetails());
} catch (RateLimitException $e) {
    echo "Rate limited. Retry after: " . $e->getRetryAfter() . " seconds";
} catch (EmailEngineException $e) {
    echo "API error: " . $e->getMessage();
}
```

## Raw API Requests

For endpoints not covered by resource classes:

```php
// GET request
$response = $client->request('GET', '/v1/some-endpoint', query: ['param' => 'value']);

// POST request
$response = $client->request('POST', '/v1/some-endpoint', data: ['key' => 'value']);

// With custom headers
$response = $client->request('POST', '/v1/some-endpoint',
    data: ['key' => 'value'],
    headers: ['X-Custom-Header' => 'value']
);
```

## Testing

### Local Testing

```bash
# Install dependencies
make install

# Run unit tests
make test

# Run tests with coverage
make test-coverage

# Run static analysis
make phpstan

# Check code style
make lint
```

### Docker Testing

Test across multiple PHP versions using Docker:

```bash
# Run unit tests on PHP 8.3
make docker-test

# Run tests on all PHP versions (8.1, 8.2, 8.3, 8.4)
make docker-test-all

# Run integration tests with real EmailEngine
make docker-integration
```

### Integration Testing

Integration tests run against a real EmailEngine instance:

```bash
# Start EmailEngine and Redis
make docker-up

# Run integration tests (auto-generates access token)
make docker-integration

# View EmailEngine logs
make docker-logs

# Stop services
make docker-down
```

**Note:** EmailEngine without a license suspends workers after 15 minutes. Integration tests are designed to complete within this time window. If you need more time, restart EmailEngine:

```bash
docker compose restart emailengine
```

### Manual Integration Testing

To run integration tests against your own EmailEngine instance:

```bash
# Generate a token using EmailEngine CLI
emailengine tokens issue -d "Test" -s "*" --dbs.redis="redis://localhost:6379"

# Run tests with your token
EMAILENGINE_ACCESS_TOKEN="your-token" \
EMAILENGINE_BASE_URL="http://localhost:3000" \
./vendor/bin/phpunit --testsuite integration
```

## Legacy Compatibility

The SDK maintains backward compatibility with the old API:

```php
// Old way (still works, but deprecated)
use EmailEnginePhp\EmailEngine;

$ee = new EmailEngine([
    'access_token' => 'token',
    'ee_base_url' => 'http://localhost:3000',
    'service_secret' => 'secret',
    'redirect_url' => 'http://callback.url',
]);

$ee->get_webhook_settings();
$ee->set_webhook_settings(['enabled' => true]);
$ee->get_authentication_url(['account' => null]);
```

## License

MIT License - see [LICENSE](LICENSE) file.

## Links

- [EmailEngine](https://emailengine.app/) - Self-hosted email gateway
- [EmailEngine Documentation](https://api.emailengine.app/) - API reference
- [GitHub Repository](https://github.com/postalsys/emailengine-php)

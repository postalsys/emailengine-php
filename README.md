# EmailEngine

Helper library for [EmailEngine](https://emailengine.app/), the app to access any email account to receive and send emails using an easy-to-use REST API.

## Usage

Load and initialize the class

```php
use EmailEnginePhp\EmailEngine;

$ee = new EmailEngine(array(
    "access_token" => "3eb50ef80efb67885afb43844df8ae01e4eecb99c4defac3aa37ec5b8b4f1339",
    "service_secret" => "a23da152f5b88543f52420a0de0e0eb6",
    "ee_base_url" => "http://127.0.0.1:3000/",
    "redirect_url" => "http://127.0.0.1:5000/handler.php",
));
```

Where

- **access_token** is a valid Access Token to authenticate API requests
- **service_secret** is the shared secret value, set on the Service configuration page (used to sign public URLs)
- **ee_base_url** is the base URL for EmailEngine (eg. the scheme, host and port without any path parameter)
- **redirect_url** is the default Redirection URL for hosted authentication forms. Once an account is added, the user is redirected to that URL. Additionally `account` and `state` arguments are added as query parameters to this URL.

### Hosted authentication

Generate an authentication URL. If you set `null` as the account ID, then EmailEngine generates a new unique ID, otherwise whatever you specifiy will be used. If that account already exists, then the config is updated.

```php
$auth_url = $ee->get_authentication_url(array("account" => null));
header('Location: ' . $auth_url);
```

### Get webhook settings

```php
$webhook_settings = $ee->get_webhook_settings();
echo "Webhooks are " . ($webhook_settings["enabled"] ? "enabled" : "disabled") . "\n";
echo "Webhooks URL is " . $webhook_settings["url"] . "\n";
```

### Set webhook settings

You can set either all or partial setting values for webhook settings

```php
$ee->set_webhook_settings(array(
    // are webhooks enabled or not
    "enabled" => true,
    // The URL webhooks are POSTed to
    "url" => "http://127.0.0.1:5000/webhooks.php",
    // Webhooks events to listen for, "*" means all events
    "events" => array("*"),
    // Additional message headers to include, "*" means all headers. Case insensitive.
    "headers" => array("Received", "List-ID"),
    // How many bytes of text content to include in the payload. Set to 0 or false to disable
    "text" => 1024 * 1024,
));
```

### Making API requests

Class exposes a helper method to make API requests against EmailEngine.

```php
$response = $ee->request($method, $path, $payload = false);
```

Where

- **$method** is either `"get"`, `"post"`, `"put"` or `"delete"`
- **$path** is the enpoint path, eg `"/v1/stats"`. This path should also include any query arguments if needed, eg. `"/v1/settings?proxyUrl=true"`
- **$payload** is the payload array for `POST` and `PUT` requests. Eg. `array("proxyUrl" => "socks://proxy.example.com:1080")`

#### Examples

##### Register a new account

```php
// Register a new account
$account_response = $ee->request('post', '/v1/account', array(
    'account' => "example", // or null if you want it to be autogenerated by EmailEngine
    'name' => 'Andris Reinman',
    'email' => 'andris@ekiri.ee',
    'imap' => array(
        'auth' => array(
            'user' => 'andris',
            'pass' => 'secretvalue',
        ),
        'host' => 'turvaline.ekiri.ee',
        'port' => 993,
        'secure' => true,
    ),
    'smtp' => array(
        'auth' => array(
            'user' => 'andris',
            'pass' => 'secretvalue',
        ),
        'host' => 'turvaline.ekiri.ee',
        'port' => 465,
        'secure' => true,
    ),
));
```

##### Wait until the account is connected

```php
$account_id = "example";
$account_connected = false;
while (!$account_connected) {
    sleep(1);
    $account_info = $ee->request('get', "/v1/account/$account_id");
    if ($account_info["state"] == "connected") {
        $account_connected = true;
        echo "Account $account_id is connected\n";
    } else {
        echo "Account $account_id is ${account_info['state']}...\n";
    }
}
```

##### Send an email

```php
$account_id = "example";
$submit_response = $ee->request('post', "/v1/account/$account_id/submit", array(
    "from" => array(
        "name" => "Andris Reinman",
        "address" => "andris@ekiri.ee",
    ),
    "to" => array(
        array(
            "name" => "Ethereal",
            "address" => "andris@ethereal.email",
        ))
    ,
    "subject" => "Test message",
    "text" => "Hello from myself!",
    "html" => "<p>Hello from myself!</p>",
));
```

If sending succeeds, then the sent message is also uploaded to the Sent Mail folder.

##### Download an attachment

```php
$account_id = "example";
$attachment_id = "AAAAAQAABRQ";
$ee->download("/v1/account/$account_id/attachment/$attachment_id");
```

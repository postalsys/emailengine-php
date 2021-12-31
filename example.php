<?php

require __DIR__ . '/vendor/autoload.php';

use EmailEnginePhp\EmailEngine;

$ee = new EmailEngine(array(
    "access_token" => "3eb50ef80efb67885afb43844df8ae01e4eecb99c4defac3aa37ec5b8b4f1339",
    "service_secret" => "a23da152f5b88543f52420a0de0e0eb6",
    "ee_base_url" => "http://127.0.0.1:3000/",
    "redirect_url" => "http://127.0.0.1:5000/handler.php",
));

echo $ee->get_authentication_url(array("account" => null));

echo "\n";

$ee->set_webhook_settings(array(
    "enabled" => true,
    "url" => "http://127.0.0.1:5000/webhooks.php",
    "events" => array("*"),
    "headers" => array("Received", "List-ID"),
    "text" => 1024 * 1024,
));

print_r($ee->get_webhook_settings());

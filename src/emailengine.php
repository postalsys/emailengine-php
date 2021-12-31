<?php

namespace EmailEnginePhp;

class EmailEngine
{

    private $access_token;
    private $service_secret;
    public $ee_base_url;
    public $redirect_url;
    public $http_client;

    public function __construct($opts)
    {
        $this->http_client = new \GuzzleHttp\Client();

        if (isset($opts)) {
            $this->setup($opts);
        }
    }

    /**
     * Encode value to a urlsafe base64
     *
     * @param string $val Value to encode
     *
     * @return string Urlsafe base64 encoded string
     */
    public function base64_encode_urlsafe($val)
    {
        return str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($val));
    }

    /**
     * Decode a urlsafe base64 encoded value
     *
     * @param string $val Urlsafe base64 encoded string
     *
     * @return string Decoded value
     */
    public function base64_decode_urlsafe($val)
    {
        $data = str_replace(array('-', '_'), array('+', '/'), $val);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }

    /**
     * Signs a value with SHA256-HMAC secret
     *
     * @param string $val Value to be signed
     * @param string $service_secret HMAC secret
     *
     * @return string Unencoded signature string
     */
    public function sign_request($val, $service_secret)
    {
        return hash_hmac("sha256", $val, $service_secret, true);
    }

    /**
     * Generates a redirect URL for EmailEngine's hosted authentication page
     *
     * @param mixed $data Data to be encoded into the URL
     *
     * @return string Redirect URL
     */
    public function get_authentication_url($data)
    {
        if (empty($data["redirect_url"])) {
            $data["redirect_url"] = $this->redirect_url;
        }

        $data["redirectUrl"] = (!empty($data["redirectUrl"])) ? $data["redirectUrl"] : $data["redirect_url"];
        unset($data["redirect_url"]);

        $data_json = json_encode($data);
        $signature = $this->sign_request($data_json, $this->service_secret);

        return $this->ee_base_url . '/accounts/new?data=' . $this->base64_encode_urlsafe($data_json) . '&sig=' . $this->base64_encode_urlsafe($signature);
    }

    public function set_webhook_settings($opts)
    {
        $settings = array();

        if (isset($opts['enabled'])) {
            // toggle enabled status
            $settings['webhooksEnabled'] = !empty($opts['enabled']) ? true : false;
        }

        if (isset($opts['url'])) {
            // set webhooks URL
            $settings['webhooks'] = $opts['url'];
        }

        if (isset($opts['events'])) {
            // set webhooks URL
            $settings['webhookEvents'] = $opts['events'];
        }

        if (isset($opts['headers'])) {
            // set webhooks URL
            $settings['notifyHeaders'] = $opts['headers'];
        }

        if (isset($opts['text'])) {
            if (empty($opts['text']) || $opts['text'] == 0) {
                $settings['notifyText'] = false;
            } else {
                $settings['notifyText'] = true;
                if (is_numeric($opts['text'])) {
                    $settings['notifyTextSize'] = intval($opts['text']);
                }
            }
        }

        $data = $this->request('post', '/v1/settings', $settings);

        return isset($data['updated']);
    }

    public function get_webhook_settings()
    {
        $data = $this->request('get', '/v1/settings?webhooksEnabled=true&webhookEvents=true&webhooks=true&notifyHeaders=true&notifyText=true&notifyTextSize=true');

        $settings = array(
            'enabled' => isset($data['webhooksEnabled']) && !empty($data['webhooksEnabled']) && $data['webhooksEnabled'] ? true : false,
            'url' => isset($data['webhooks']) && !empty($data['webhooks']) ? $data['webhooks'] : '',
            'events' => isset($data['webhookEvents']) && !empty($data['webhookEvents']) ? $data['webhookEvents'] : array(),
            'headers' => isset($data['notifyHeaders']) && !empty($data['notifyHeaders']) ? $data['notifyHeaders'] : array(),
            'text' => isset($data['notifyText']) && !empty($data['notifyText']) && $data['notifyText'] ? $data['notifyTextSize'] : false,
        );

        return $settings;
    }

    public function setup($opts)
    {
        if (isset($opts['ee_base_url']) && !empty($opts['ee_base_url'])) {
            $this->ee_base_url = preg_replace('{/$}', '', $opts['ee_base_url']);
        }

        if (isset($opts['service_secret']) && !empty($opts['service_secret'])) {
            $this->service_secret = $opts['service_secret'];
        }

        if (isset($opts['access_token']) && !empty($opts['access_token'])) {
            $this->access_token = $opts['access_token'];
        }

        if (isset($opts['redirect_url']) && !empty($opts['redirect_url'])) {
            $this->redirect_url = $opts['redirect_url'];
        }
    }

    public function request($method, $path, $payload = false)
    {
        $opts = array();
        if (isset($payload) && !empty($payload)) {
            $opts["json"] = $payload;
        }

        $opts['headers'] = array('Authorization' => 'Bearer ' . $this->access_token);

        $r = $this->http_client->request(strtoupper($method), $this->ee_base_url . $path, $opts);

        if ($r->getStatusCode() != 200) {
            throw new Exception('Invalid HTTP response ' . $r->getStatusCode());
        }

        $data = json_decode($r->getBody(), true);

        return $data;
    }
}

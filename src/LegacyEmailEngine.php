<?php

/**
 * Legacy compatibility class
 *
 * @deprecated Use Postalsys\EmailEnginePhp\EmailEngine instead
 */

namespace EmailEnginePhp;

use Postalsys\EmailEnginePhp\EmailEngine as ModernEmailEngine;

/**
 * Legacy EmailEngine class for backward compatibility
 *
 * This class maintains compatibility with code using the old EmailEnginePhp namespace.
 * New code should use Postalsys\EmailEnginePhp\EmailEngine instead.
 *
 * @deprecated Use Postalsys\EmailEnginePhp\EmailEngine instead
 */
class EmailEngine extends ModernEmailEngine
{
    public $ee_base_url;
    public $redirect_url;
    public $http_client;

    /**
     * Create a new EmailEngine instance using legacy array options
     *
     * @param array{
     *     access_token?: string,
     *     ee_base_url?: string,
     *     service_secret?: string,
     *     redirect_url?: string
     * } $opts Configuration options
     */
    public function __construct($opts = [])
    {
        $accessToken = $opts['access_token'] ?? '';
        $baseUrl = $opts['ee_base_url'] ?? 'http://localhost:3000';
        $serviceSecret = $opts['service_secret'] ?? null;
        $redirectUrl = $opts['redirect_url'] ?? null;

        // Store public properties for legacy compatibility
        $this->ee_base_url = rtrim($baseUrl, '/');
        $this->redirect_url = $redirectUrl;
        $this->http_client = new \GuzzleHttp\Client();

        parent::__construct(
            accessToken: $accessToken,
            baseUrl: $baseUrl,
            serviceSecret: $serviceSecret,
            redirectUrl: $redirectUrl
        );
    }

    /**
     * Legacy setup method for reconfiguring the client
     *
     * @deprecated Configure via constructor instead
     * @param array $opts Configuration options
     */
    public function setup($opts): void
    {
        trigger_error(
            'EmailEnginePhp\EmailEngine::setup() is deprecated. Configure via constructor instead.',
            E_USER_DEPRECATED
        );

        if (isset($opts['ee_base_url']) && !empty($opts['ee_base_url'])) {
            $this->ee_base_url = rtrim($opts['ee_base_url'], '/');
        }

        if (isset($opts['redirect_url']) && !empty($opts['redirect_url'])) {
            $this->redirect_url = $opts['redirect_url'];
        }
    }
}

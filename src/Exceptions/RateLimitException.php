<?php

declare(strict_types=1);

namespace Postalsys\EmailEnginePhp\Exceptions;

/**
 * Exception thrown when rate limit is exceeded (429 responses)
 */
class RateLimitException extends EmailEngineException
{
    public function __construct(
        string $message = 'Rate limit exceeded',
        ?string $errorCode = null,
        ?array $details = null,
        protected readonly ?int $retryAfter = null,
        int $code = 429,
    ) {
        parent::__construct($message, $errorCode, $details, $code);
    }

    /**
     * Get the number of seconds to wait before retrying
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}

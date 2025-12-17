<?php

declare(strict_types=1);

namespace Postalsys\EmailEnginePhp\Exceptions;

use Exception;
use Throwable;

/**
 * Base exception for all EmailEngine SDK errors
 */
class EmailEngineException extends Exception
{
    public function __construct(
        string $message = '',
        protected readonly ?string $errorCode = null,
        protected readonly ?array $details = null,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the API error code if available
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * Get additional error details if available
     */
    public function getDetails(): ?array
    {
        return $this->details;
    }
}

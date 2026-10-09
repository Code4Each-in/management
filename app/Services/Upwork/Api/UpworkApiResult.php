<?php

namespace App\Services\Upwork\Api;

/** Outcome of one read-only Upwork call. */
class UpworkApiResult
{
    /** @var bool */
    public $ok;
    /** @var array[] rows in PMS ingestion format */
    public $rows;
    /** @var string|null */
    public $nextCursor;
    /** @var string|null rate_limited, auth, not_configured, server_error, timeout, invalid_response, not_found */
    public $errorCode;
    /** @var string|null */
    public $error;
    /** @var int */
    public $durationMs;

    public function __construct(bool $ok, array $rows = [], ?string $nextCursor = null, ?string $errorCode = null, ?string $error = null, int $durationMs = 0)
    {
        $this->ok = $ok;
        $this->rows = $rows;
        $this->nextCursor = $nextCursor;
        $this->errorCode = $errorCode;
        $this->error = $error;
        $this->durationMs = $durationMs;
    }

    public static function rows(array $rows, ?string $nextCursor = null, int $durationMs = 0): self
    {
        return new self(true, $rows, $nextCursor, null, null, $durationMs);
    }

    public static function failure(string $code, string $error, int $durationMs = 0): self
    {
        return new self(false, [], null, $code, $error, $durationMs);
    }

    public function rateLimited(): bool
    {
        return $this->errorCode === 'rate_limited';
    }
}

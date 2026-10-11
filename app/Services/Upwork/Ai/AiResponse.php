<?php

namespace App\Services\Upwork\Ai;

/** Outcome of one provider call: parsed data or a classified error, plus token usage. */
class AiResponse
{
    /** @var bool */
    public $ok;
    /** @var array|null */
    public $data;
    /** @var string|null machine-readable: rate_limited, quota_exhausted, auth, timeout, server_error, malformed, schema_invalid, refusal, incomplete, disabled, budget, ... */
    public $errorCode;
    /** @var string|null */
    public $error;
    /** @var bool whether trying again may succeed */
    public $retryable;
    /** @var array{input: int, cached_input: int, output: int, reasoning: int} */
    public $usage;
    /** @var int */
    public $durationMs;

    public function __construct(bool $ok, ?array $data, ?string $errorCode, ?string $error, bool $retryable, array $usage = [], int $durationMs = 0)
    {
        $this->ok = $ok;
        $this->data = $data;
        $this->errorCode = $errorCode;
        $this->error = $error;
        $this->retryable = $retryable;
        $this->usage = array_merge(['input' => 0, 'cached_input' => 0, 'output' => 0, 'reasoning' => 0], $usage);
        $this->durationMs = $durationMs;
    }

    public static function success(array $data, array $usage, int $durationMs): self
    {
        return new self(true, $data, null, null, false, $usage, $durationMs);
    }

    public static function failure(string $code, string $error, bool $retryable, array $usage = [], int $durationMs = 0): self
    {
        return new self(false, null, $code, $error, $retryable, $usage, $durationMs);
    }
}

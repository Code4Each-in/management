<?php

namespace App\Services\Upwork\Ai;

/**
 * One structured AI request. The static part (instructions: capabilities,
 * catalog, rules) is kept separate from the dynamic part (this job) so
 * providers that cache prompt prefixes can reuse the static part.
 */
class AiRequest
{
    /** @var string */
    public $type;
    /** @var string */
    public $promptVersion;
    /** @var string */
    public $staticContext;
    /** @var string */
    public $dynamicContext;
    /** @var array JSON schema the answer must match */
    public $schema;
    /** @var string */
    public $schemaName;
    /** @var int|null */
    public $maxOutputTokens;

    public function __construct(string $type, string $promptVersion, string $staticContext, string $dynamicContext, array $schema, string $schemaName, ?int $maxOutputTokens = null)
    {
        $this->type = $type;
        $this->promptVersion = $promptVersion;
        $this->staticContext = $staticContext;
        $this->dynamicContext = $dynamicContext;
        $this->schema = $schema;
        $this->schemaName = $schemaName;
        $this->maxOutputTokens = $maxOutputTokens;
    }

    /** Conservative token estimate (about 3.5 characters per token) for budget reservations. */
    public function estimatedInputTokens(): int
    {
        return (int) ceil((mb_strlen($this->staticContext) + mb_strlen($this->dynamicContext) + strlen(json_encode($this->schema))) / 3.5);
    }
}

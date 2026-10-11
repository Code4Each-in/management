<?php

namespace App\Services\Upwork\Ai;

/** An AI call was refused before it was made (budget, limits, disabled, unpriced model, ...). */
class AiBudgetExceeded extends \RuntimeException
{
    /** @var string */
    public $reason;

    public function __construct(string $reason, string $message)
    {
        parent::__construct($message);
        $this->reason = $reason;
    }
}

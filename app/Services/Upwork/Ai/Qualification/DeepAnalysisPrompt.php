<?php

namespace App\Services\Upwork\Ai\Qualification;

/**
 * Deep analysis, requested by a person: the qualification answer plus a bid
 * angle, approach, experience to highlight and red flags. Same model and
 * fact labelling; larger token caps and its own sub-budget (AiBudgetGuard).
 */
class DeepAnalysisPrompt extends QualificationPrompt
{
    const TYPE = 'deep';
    const VERSION = 'deep-v1';
    const SCHEMA_NAME = 'upwork_job_deep_analysis';

    protected function extraRules(): string
    {
        return <<<'TXT'

8. Also give: proposal_angle (one or two sentences: why Code4Each, for this client), approach (3-6 short steps),
   experience_to_highlight (capabilities from the list above that matter here; never invent past projects),
   and red_flags (each labelled FACT, INFERENCE or ASSUMPTION).
TXT;
    }

    public static function schema(): array
    {
        $schema = parent::schema();
        $extra = [
            'proposal_angle' => ['type' => 'string'],
            'approach' => ['type' => 'array', 'items' => ['type' => 'string']],
            'experience_to_highlight' => ['type' => 'array', 'items' => ['type' => 'string']],
            'red_flags' => ['type' => 'array', 'items' => [
                'type' => 'object', 'additionalProperties' => false, 'required' => ['flag', 'kind'],
                'properties' => ['flag' => ['type' => 'string'], 'kind' => ['type' => 'string', 'enum' => ['FACT', 'INFERENCE', 'ASSUMPTION']]],
            ]],
        ];
        $schema['properties'] += $extra;
        $schema['required'] = array_keys($schema['properties']);

        return $schema;
    }
}

<?php

namespace App\Http\Requests\Upwork;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Used both by the human edit form and by the agent's draft endpoint.
 * Authorization is done in the controllers (policy differs per caller).
 */
class UpdateProposalDraftRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'proposal_draft' => ['required', 'string', 'max:20000'],
            'proposed_rate' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'screening_answers' => ['nullable', 'array', 'max:20'],
            'screening_answers.*.question' => ['required_with:screening_answers', 'string', 'max:2000'],
            'screening_answers.*.answer' => ['nullable', 'string', 'max:10000'],
        ];
    }
}

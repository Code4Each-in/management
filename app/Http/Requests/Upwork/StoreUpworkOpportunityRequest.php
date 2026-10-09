<?php

namespace App\Http\Requests\Upwork;

use App\Models\UpworkOpportunity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUpworkOpportunityRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && $this->user()->can('ingest', UpworkOpportunity::class);
    }

    protected function prepareForValidation()
    {
        $normalized = [];

        foreach (['job_type', 'recommendation'] as $field) {
            if (is_string($this->input($field))) {
                $normalized[$field] = strtolower(trim($this->input($field)));
            }
        }

        // The agent may send lists for these; store them as readable text.
        foreach (['match_summary', 'relevant_experience', 'risks'] as $field) {
            if (is_array($this->input($field))) {
                $normalized[$field] = implode("\n", array_map(fn($line) => '- ' . (is_scalar($line) ? $line : json_encode($line)), $this->input($field)));
            }
        }

        $this->merge($normalized);
    }

    public function rules()
    {
        $score = ['nullable', 'numeric', 'min:0', 'max:100'];

        return [
            'upwork_job_id' => ['required', 'string', 'max:64', 'regex:/^~?[A-Za-z0-9_-]+$/'],
            'upwork_url' => ['nullable', 'string', 'max:500', 'url', function ($attribute, $value, $fail) {
                $host = strtolower((string) parse_url($value, PHP_URL_HOST));
                if (parse_url($value, PHP_URL_SCHEME) !== 'https' || !($host === 'upwork.com' || str_ends_with($host, '.upwork.com'))) {
                    $fail('The upwork url must be an https upwork.com URL.');
                }
            }],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:50000'],
            'job_type' => ['nullable', Rule::in(['hourly', 'fixed'])],
            'hourly_min' => ['nullable', 'numeric', 'min:0'],
            'hourly_max' => ['nullable', 'numeric', 'min:0'],
            'fixed_budget' => ['nullable', 'numeric', 'min:0'],
            'experience_level' => ['nullable', 'string', 'max:50'],
            'duration' => ['nullable', 'string', 'max:100'],

            'client_country' => ['nullable', 'string', 'max:100'],
            'client_timezone' => ['nullable', 'string', 'max:100'],
            'client_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'client_reviews_count' => ['nullable', 'integer', 'min:0'],
            'client_total_spent' => ['nullable', 'numeric', 'min:0'],
            'client_hire_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'client_jobs_posted' => ['nullable', 'integer', 'min:0'],
            'client_jobs_hired' => ['nullable', 'integer', 'min:0'],
            'payment_verified' => ['nullable', 'boolean'],

            'proposals_count' => ['nullable', 'integer', 'min:0'],
            'proposals_tier' => ['nullable', 'string', 'max:20'],
            'contractor_type' => ['nullable', 'string', 'max:50'],
            'workload' => ['nullable', 'string', 'max:30'],
            'job_activity' => ['nullable', 'array'],
            'job_activity.*' => ['nullable', 'integer', 'min:0'],
            'agent_signals' => ['nullable', 'array'],
            'agent_signals.technologies' => ['nullable', 'array', 'max:100'],
            'agent_signals.technologies.*' => ['string', 'max:100'],
            'agent_signals.scope_size' => ['nullable', Rule::in(['small', 'medium', 'large'])],
            'agent_signals.project_clarity' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'agent_signals.is_individual_only' => ['nullable', 'boolean'],
            'agent_signals.previous_client' => ['nullable', 'boolean'],
            'upwork_client_id' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_~-]+$/'],
            'client_open_jobs' => ['nullable', 'integer', 'min:0'],
            'client_work_history' => ['nullable', 'array', 'max:20'],
            'client_work_history.*.title' => ['nullable', 'string', 'max:255'],
            'client_work_history.*.type' => ['nullable', 'string', 'max:20'],
            'client_work_history.*.status' => ['nullable', 'string', 'max:20'],
            'client_work_history.*.started' => ['nullable', 'date'],
            'client_work_history.*.ended' => ['nullable', 'date'],
            'client_work_history.*.feedback_score' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'client_work_history.*.feedback_to_client_score' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'interviews_count' => ['nullable', 'integer', 'min:0'],
            'connects_required' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'posted_at' => ['nullable', 'date'],
            'fetched_at' => ['nullable', 'date'],

            'score' => $score,
            'technical_fit_score' => $score,
            'experience_fit_score' => $score,
            'client_quality_score' => $score,
            'commercial_value_score' => $score,
            'long_term_score' => $score,
            'ai_automation_score' => $score,
            'competition_score' => $score,
            'score_breakdown' => ['nullable', 'array'],
            'scoring_version' => ['nullable', 'string', 'max:50'],
            'hard_filters' => ['nullable', 'array', 'max:50'],
            'hard_filters.*' => ['string', 'max:255'],

            'recommendation' => ['nullable', Rule::in(array_keys(UpworkOpportunity::RECOMMENDATIONS))],
            'match_summary' => ['nullable', 'string', 'max:20000'],
            'relevant_experience' => ['nullable', 'string', 'max:20000'],
            'risks' => ['nullable', 'string', 'max:20000'],
            'technologies' => ['nullable', 'array', 'max:100'],
            'technologies.*' => ['string', 'max:100'],

            'raw_data' => ['nullable', 'array'],
        ];
    }
}

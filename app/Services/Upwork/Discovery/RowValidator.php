<?php

namespace App\Services\Upwork\Discovery;

use App\Http\Requests\Upwork\StoreUpworkOpportunityRequest;
use Illuminate\Support\Facades\Validator;

/** Validates job rows one by one with the full-ingestion rules, so one bad row never fails a batch. */
class RowValidator
{
    /** @return array{0: array[], 1: array} [valid rows, invalid index => {upwork_job_id, errors}] */
    public static function split(array $rows): array
    {
        $rules = (new StoreUpworkOpportunityRequest)->rules();
        $valid = [];
        $invalid = [];
        foreach ($rows as $i => $row) {
            $row = is_array($row) ? self::normalize($row) : [];
            $validator = Validator::make($row, $rules);
            if ($validator->fails()) {
                $invalid[$i] = ['upwork_job_id' => $row['upwork_job_id'] ?? null, 'errors' => $validator->errors()->all()];
            } else {
                $valid[] = $validator->validated();
            }
        }

        return [$valid, $invalid];
    }

    /** Same normalization full ingestion applies before validation. */
    public static function normalize(array $row): array
    {
        foreach (['job_type', 'recommendation'] as $field) {
            if (isset($row[$field]) && is_string($row[$field])) {
                $row[$field] = strtolower(trim($row[$field]));
            }
        }

        return $row;
    }
}

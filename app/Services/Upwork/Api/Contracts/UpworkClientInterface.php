<?php

namespace App\Services\Upwork\Api\Contracts;

use App\Services\Upwork\Api\UpworkApiResult;

/**
 * Read-only access to Upwork job data. There are exactly two operations,
 * search and job detail; nothing in this interface can write to Upwork.
 * Rows come back in the PMS ingestion format.
 */
interface UpworkClientInterface
{
    public function name(): string;

    public function isConfigured(): bool;

    /** One page of search results for a QueryPlanner query (params) and an optional cursor. */
    public function search(array $params, ?string $cursor = null): UpworkApiResult;

    /** Full details of one job (rows[0]), by numeric id or ~02 ciphertext. */
    public function job(string $jobId): UpworkApiResult;
}

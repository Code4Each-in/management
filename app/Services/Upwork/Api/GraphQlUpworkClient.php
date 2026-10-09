<?php

namespace App\Services\Upwork\Api;

use App\Models\UpworkApiCredential;
use App\Services\Upwork\Api\Contracts\UpworkClientInterface;
use App\Services\Upwork\UpworkOpportunityService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

/**
 * Official Upwork GraphQL API, read-only. Exactly two allowlisted query
 * documents exist (job search, job detail) and every document passes
 * ReadOnlyGraphQl before it is sent. There is no method that takes a
 * caller-supplied GraphQL document.
 *
 * Field names follow Upwork's public GraphQL schema; mapping is tolerant
 * (missing fields become null), and must be re-checked against the live
 * schema once the API key is approved (docs/upwork-opportunities.md).
 */
class GraphQlUpworkClient implements UpworkClientInterface
{
    const OPERATIONS = [
        'JobSearch' => <<<'GQL'
query JobSearch($filter: MarketplaceJobPostingsSearchFilter, $sort: [MarketplaceJobPostingSearchSortAttribute]) {
  marketplaceJobPostingsSearch(marketPlaceJobFilter: $filter, searchType: USER_JOBS_SEARCH, sortAttributes: $sort) {
    totalCount
    pageInfo { hasNextPage endCursor }
    edges {
      node {
        id ciphertext title description createdDateTime publishedDateTime
        experienceLevel duration durationLabel engagement
        amount { rawValue }
        hourlyBudgetMin { rawValue }
        hourlyBudgetMax { rawValue }
        skills { name prettyName }
        totalApplicants
        client {
          totalHires totalPostedJobs totalReviews totalFeedback verificationStatus
          location { country }
          totalSpent { rawValue }
        }
      }
    }
  }
}
GQL,
        'JobDetail' => <<<'GQL'
query JobDetail($id: ID!) {
  marketplaceJobPosting(id: $id) {
    id
    content { title description }
    classification { category { preferredLabel } subCategory { preferredLabel } skills { preferredLabel } }
    contractTerms {
      contractType personsToHire experienceLevel
      fixedPriceContractTerms { amount { rawValue } }
      hourlyContractTerms { engagementDuration { label weeks } hourlyBudgetMin hourlyBudgetMax engagementType }
    }
    contractorSelection { qualification { contractorType } }
    activityStat {
      jobActivity { lastClientActivity invitesSent totalInvitedToInterview totalHired totalOffered }
      applicationsBidStats { avgRateBid { rawValue } }
    }
    clientCompanyPublic { id country { name } paymentVerification { paymentVerified } }
  }
}
GQL,
    ];

    /** QueryPlanner param => Upwork filter mapping (values that need translating). */
    const EXPERIENCE = ['entry_level' => 'ENTRY_LEVEL', 'intermediate' => 'INTERMEDIATE', 'expert' => 'EXPERT'];
    const WORKLOAD = ['full_time' => 'FULL_TIME', 'part_time' => 'PART_TIME', 'as_needed' => 'AS_NEEDED'];

    /** @var UpworkOAuth */
    private $oauth;

    public function __construct(?UpworkOAuth $oauth = null)
    {
        $this->oauth = $oauth ?? new UpworkOAuth();
    }

    public function name(): string
    {
        return 'upwork-graphql';
    }

    public function isConfigured(): bool
    {
        $credential = UpworkApiCredential::current();

        return $this->oauth->isConfigured() && $credential && $credential->isConnected();
    }

    public function search(array $params, ?string $cursor = null): UpworkApiResult
    {
        $result = $this->execute('JobSearch', ['filter' => self::searchFilter($params, $cursor), 'sort' => [['field' => 'RECENCY']]]);
        if (!$result->ok) {
            return $result;
        }

        $data = $result->rows[0];
        $rows = [];
        foreach ((array) Arr::get($data, 'marketplaceJobPostingsSearch.edges', []) as $edge) {
            if ($row = self::mapSearchNode((array) ($edge['node'] ?? []))) {
                $rows[] = $row;
            }
        }
        $next = Arr::get($data, 'marketplaceJobPostingsSearch.pageInfo.hasNextPage') ? Arr::get($data, 'marketplaceJobPostingsSearch.pageInfo.endCursor') : null;

        return UpworkApiResult::rows($rows, $next, $result->durationMs);
    }

    public function job(string $jobId): UpworkApiResult
    {
        $result = $this->execute('JobDetail', ['id' => UpworkOpportunityService::normalizeJobId($jobId)]);
        if (!$result->ok) {
            return $result;
        }

        $node = Arr::get($result->rows[0], 'marketplaceJobPosting');
        if (!is_array($node)) {
            return UpworkApiResult::failure('not_found', "Job {$jobId} not found or not visible.", $result->durationMs);
        }

        return UpworkApiResult::rows([self::mapJobDetail($node)], null, $result->durationMs);
    }

    /** Planner params -> Upwork MarketplaceJobPostingsSearchFilter. Only read filters exist here. */
    public static function searchFilter(array $p, ?string $cursor): array
    {
        $filter = array_filter([
            'searchExpression_eq' => $p['query'] ?? null,
            'titleExpression_eq' => $p['title'] ?? null,
            'jobType_eq' => isset($p['job_type']) ? strtoupper($p['job_type']) : null,
            'experienceLevel_eq' => self::EXPERIENCE[$p['experience_level'] ?? ''] ?? null,
            'workload_eq' => self::WORKLOAD[$p['workload'] ?? ''] ?? null,
            'verifiedPaymentOnly_eq' => !empty($p['verified_payment_only']) ? true : null,
            'hourlyRate_eq' => isset($p['rate_min']) ? ['rangeStart' => (float) $p['rate_min']] : null,
            'budgetRange_eq' => isset($p['budget_min']) ? ['rangeStart' => (float) $p['budget_min']] : null,
            'proposalRange_eq' => isset($p['proposals_max']) ? ['rangeEnd' => (int) $p['proposals_max']] : null,
        ], fn($v) => $v !== null && $v !== '');
        $filter['pagination_eq'] = array_filter(['first' => max(1, min(50, (int) ($p['limit'] ?? 10))), 'after' => $cursor], fn($v) => $v !== null);

        return $filter;
    }

    /** One search node -> PMS row (stage 1: search data only). */
    public static function mapSearchNode(array $n): ?array
    {
        $id = $n['id'] ?? null;
        $cipher = $n['ciphertext'] ?? null;
        if (!$id && !$cipher) {
            return null;
        }
        $jobId = UpworkOpportunityService::normalizeJobId((string) ($id ?: $cipher));
        $hourlyMin = self::num(Arr::get($n, 'hourlyBudgetMin.rawValue'));
        $hourlyMax = self::num(Arr::get($n, 'hourlyBudgetMax.rawValue'));
        $amount = self::num(Arr::get($n, 'amount.rawValue'));
        $hires = self::num(Arr::get($n, 'client.totalHires'));
        $posted = self::num(Arr::get($n, 'client.totalPostedJobs'));

        return array_filter([
            'upwork_job_id' => $jobId,
            'upwork_url' => 'https://www.upwork.com/jobs/' . ($cipher ?: "~02{$jobId}"),
            'title' => $n['title'] ?? null,
            'description' => $n['description'] ?? null,
            'job_type' => ($hourlyMin || $hourlyMax) ? 'hourly' : ($amount ? 'fixed' : null),
            'hourly_min' => $hourlyMin,
            'hourly_max' => $hourlyMax,
            'fixed_budget' => ($hourlyMin || $hourlyMax) ? null : $amount,
            'experience_level' => isset($n['experienceLevel']) ? strtolower((string) $n['experienceLevel']) : null,
            'duration' => $n['durationLabel'] ?? null,
            'workload' => isset($n['engagement']) ? strtolower(str_replace([' ', '-'], '_', (string) $n['engagement'])) : null,
            'client_country' => Arr::get($n, 'client.location.country'),
            'client_rating' => self::num(Arr::get($n, 'client.totalFeedback')),
            'client_reviews_count' => self::num(Arr::get($n, 'client.totalReviews')),
            'client_total_spent' => self::num(Arr::get($n, 'client.totalSpent.rawValue')),
            'client_jobs_posted' => $posted,
            'client_jobs_hired' => $hires,
            'client_hire_rate' => ($posted && $hires !== null) ? min(100, round($hires / $posted * 100)) : null,
            'payment_verified' => isset($n['client']['verificationStatus']) ? strtoupper((string) $n['client']['verificationStatus']) === 'VERIFIED' : null,
            'proposals_count' => self::num($n['totalApplicants'] ?? null),
            'posted_at' => $n['publishedDateTime'] ?? ($n['createdDateTime'] ?? null),
            'fetched_at' => now()->toIso8601String(),
            'raw_data' => ['source' => 'upwork-graphql', 'skills' => array_values(array_filter(array_map(fn($s) => $s['prettyName'] ?? ($s['name'] ?? null), (array) ($n['skills'] ?? []))))],
        ], fn($v) => $v !== null);
    }

    /** Job detail -> PMS row (stage 2). Merged over the search row by the runner. */
    public static function mapJobDetail(array $n): array
    {
        $hourly = Arr::get($n, 'contractTerms.hourlyContractTerms');
        $fixed = self::num(Arr::get($n, 'contractTerms.fixedPriceContractTerms.amount.rawValue'));
        $type = strtolower((string) Arr::get($n, 'contractTerms.contractType'));

        return array_filter([
            'upwork_job_id' => UpworkOpportunityService::normalizeJobId((string) ($n['id'] ?? '')),
            'title' => Arr::get($n, 'content.title'),
            'description' => Arr::get($n, 'content.description'),
            'job_type' => $type === 'hourly' || $hourly ? 'hourly' : ($type === 'fixed_price' || $type === 'fixed' || $fixed ? 'fixed' : null),
            'hourly_min' => self::num(Arr::get($hourly, 'hourlyBudgetMin')),
            'hourly_max' => self::num(Arr::get($hourly, 'hourlyBudgetMax')),
            'fixed_budget' => $fixed,
            'experience_level' => ($e = Arr::get($n, 'contractTerms.experienceLevel')) ? strtolower((string) $e) : null,
            'duration' => Arr::get($hourly, 'engagementDuration.label'),
            'contractor_type' => self::contractorType(Arr::get($n, 'contractorSelection.qualification.contractorType')),
            'client_country' => Arr::get($n, 'clientCompanyPublic.country.name'),
            'payment_verified' => ($v = Arr::get($n, 'clientCompanyPublic.paymentVerification.paymentVerified')) === null ? null : (bool) $v,
            'upwork_client_id' => ($c = Arr::get($n, 'clientCompanyPublic.id')) ? (string) $c : null,
            'interviews_count' => self::num(Arr::get($n, 'activityStat.jobActivity.totalInvitedToInterview')),
            // Counts only: job_activity is validated as integers.
            'job_activity' => array_filter([
                'invites_sent' => Arr::get($n, 'activityStat.jobActivity.invitesSent'),
                'total_invited_to_interview' => Arr::get($n, 'activityStat.jobActivity.totalInvitedToInterview'),
                'total_hired' => Arr::get($n, 'activityStat.jobActivity.totalHired'),
                'total_offered' => Arr::get($n, 'activityStat.jobActivity.totalOffered'),
            ], fn($v) => $v !== null) ?: null,
            'fetched_at' => now()->toIso8601String(),
            'raw_data' => array_filter(['source' => 'upwork-graphql', 'skills' => array_values(array_filter(array_map(fn($s) => $s['preferredLabel'] ?? null, (array) Arr::get($n, 'classification.skills', [])))),
                'category' => Arr::get($n, 'classification.category.preferredLabel'),
                'last_client_activity' => Arr::get($n, 'activityStat.jobActivity.lastClientActivity')], fn($v) => $v !== null && $v !== []),
        ], fn($v) => $v !== null);
    }

    private static function contractorType($value): ?string
    {
        switch (strtoupper((string) $value)) {
            case 'INDEPENDENT':
            case 'INDIVIDUAL':
            case 'FREELANCERS':
                return 'Individuals only';
            case 'AGENCY':
                return 'Agencies only';
            case 'ALL':
            case 'ANY':
                return 'All freelancers';
        }

        return $value ? (string) $value : null;
    }

    private static function num($v): ?float
    {
        return is_numeric($v) ? (float) $v : null;
    }

    /** The only place a request leaves for Upwork. */
    private function execute(string $operation, array $variables): UpworkApiResult
    {
        $document = self::OPERATIONS[$operation] ?? null;
        if ($document === null) {
            throw new UpworkWriteRefused("Refused: '{$operation}' is not an allowlisted read-only operation.");
        }
        ReadOnlyGraphQl::assert($document, array_keys(self::OPERATIONS));

        $credential = UpworkApiCredential::current();
        if (!$this->oauth->isConfigured() || !$credential || !$credential->isConnected()) {
            return UpworkApiResult::failure('not_configured', 'Upwork API is not connected.');
        }

        $started = microtime(true);
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                if (!$credential->access_token || !$credential->expires_at || $credential->expires_at->lt(now()->addMinutes(5))) {
                    $credential = $this->oauth->refresh($credential);
                }

                $request = Http::withToken((string) $credential->access_token)->acceptJson()->timeout((int) config('upwork.api.timeout_seconds', 30));
                if ($credential->tenant_id) {
                    $request = $request->withHeaders(['X-Upwork-API-TenantId' => $credential->tenant_id]);
                }
                $response = $request->post((string) config('upwork.api.graphql_url'), ['query' => $document, 'variables' => $variables]);
            } catch (ConnectionException $e) {
                return UpworkApiResult::failure('timeout', 'Upwork API did not respond.', self::ms($started));
            } catch (\RuntimeException $e) {
                return UpworkApiResult::failure('auth', $e->getMessage(), self::ms($started));
            }

            if ($response->status() === 401 && $attempt === 1) {
                $credential->forceFill(['expires_at' => now()->subMinute()])->save(); // force one refresh, then retry once
                continue;
            }
            if ($response->status() === 429) {
                return UpworkApiResult::failure('rate_limited', 'Upwork API rate limit reached.', self::ms($started));
            }
            if ($response->status() === 401 || $response->status() === 403) {
                return UpworkApiResult::failure('auth', 'Upwork API refused the token (HTTP ' . $response->status() . ').', self::ms($started));
            }
            if ($response->serverError()) {
                return UpworkApiResult::failure('server_error', 'Upwork API error (HTTP ' . $response->status() . ').', self::ms($started));
            }

            $json = $response->json();
            if (!is_array($json) || (!isset($json['data']) && !isset($json['errors']))) {
                return UpworkApiResult::failure('invalid_response', 'Unexpected Upwork API response.', self::ms($started));
            }
            if (!empty($json['errors']) && empty($json['data'])) {
                $message = implode('; ', array_slice(array_filter(array_map(fn($e) => $e['message'] ?? null, (array) $json['errors'])), 0, 3));
                $code = stripos($message, 'rate') !== false ? 'rate_limited' : 'invalid_response';
                return UpworkApiResult::failure($code, 'Upwork API: ' . mb_substr($message, 0, 500), self::ms($started));
            }

            return UpworkApiResult::rows([(array) $json['data']], null, self::ms($started));
        }

        return UpworkApiResult::failure('auth', 'Upwork API refused the token after a refresh.', self::ms($started));
    }

    private static function ms(float $started): int
    {
        return (int) round((microtime(true) - $started) * 1000);
    }
}

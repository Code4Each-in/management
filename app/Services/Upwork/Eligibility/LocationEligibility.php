<?php

namespace App\Services\Upwork\Eligibility;

use App\Models\UpworkOpportunity;

/**
 * Can a freelancer based in India apply to this job? Reads the job's
 * freelancer-location requirement (never the client's own country) and
 * classifies it as eligible, restricted or unknown.
 *
 * Rules, first match wins:
 *   1. blank / missing                                   -> eligible   (blank)
 *   2. India explicitly excluded ("worldwide except India") -> restricted (india_excluded)
 *      ... but also explicitly welcomed in the same text  -> unknown    (india_conflict)
 *   3. India named ("India", "Remote - India")           -> eligible   (india_included)
 *   4. worldwide / anywhere / global                     -> eligible   (worldwide)
 *      ... with a hard restriction to somewhere else too -> unknown    (worldwide_conflict)
 *   5. a region that contains India (Asia, South Asia, APAC) -> eligible (india_region)
 *      ... with a hard restriction to somewhere else too -> unknown    (india_region_conflict)
 *   6. only places without India, plus restriction words ("US only",
 *      "Applicants must reside in Canada")               -> restricted (restricted_us / restricted_americas / restricted_region)
 *   7. only the client's own location is stated          -> eligible   (client_location_only)
 *   8. no place and no restriction wording ("Remote")    -> eligible   (no_restriction_stated)
 *   9. anything else                                     -> unknown    (unrecognized)
 *
 * The client's location ("US-based client", "we are located in Germany") is
 * removed before rules 4-9, so it is never read as a freelancer restriction.
 * Preferences ("US preferred") are not restrictions and end up unknown.
 * Pure: no database, no Upwork.
 */
class LocationEligibility
{
    /** Bump when the rules change, so a backfill can find rows classified by older rules. */
    const VERSION = 1;

    const ELIGIBLE = 'eligible';
    const RESTRICTED = 'restricted';
    const UNKNOWN = 'unknown';

    const LABELS = [
        self::ELIGIBLE => 'Eligible',
        self::RESTRICTED => 'Restricted',
        self::UNKNOWN => 'Unclear',
    ];

    /** Stored as the reason and added to hard_filters while a job is restricted. */
    const SKIP_REASON = 'Location restriction: India-based applicants are not eligible';
    const UNKNOWN_REASON = 'Location restriction unclear — manual review required';
    const OVERRIDE_ELIGIBLE_REASON = 'Marked eligible by a reviewer';

    const REASONS = [
        'blank' => 'No freelancer location restriction specified',
        'india_included' => 'India is explicitly allowed',
        'worldwide' => 'Open worldwide; India is not excluded',
        'india_region' => 'The region includes India',
        'client_location_only' => "Only the client's own location is stated; no freelancer restriction",
        'no_restriction_stated' => 'No location restriction stated',
    ];

    /** Where Upwork / the agent put the freelancer-location requirement (raw_data paths, first non-empty wins). */
    const SOURCES = [
        'freelancer_location',
        'location_requirement',
        'preferred_qualifications.location',
        'preferred_qualifications.locations',
        'preferred_qualifications.freelancer_location',
        'preferred_qualifications.location_requirement',
        'preferred_qualifications.location_restriction',
        'preferred_qualifications.preferred_location',
        'preferred_qualifications.countries',
        'preferred_qualifications.regions',
    ];

    const INDIA = '(?:india|indian|indians|bharat)';

    /** Regions that contain India. */
    const INDIA_REGIONS = [
        'south and southeast asia', 'indian subcontinent', 'asia pacific', 'south asia', 'southern asia',
        'saarc', 'apac', 'apj', 'asia', 'asian',
    ];

    /** Places that do not contain India => label. Matched longest first. */
    const PLACES = [
        // United States
        'united states of america' => 'United States', 'united states' => 'United States', 'usa' => 'United States',
        'america' => 'United States', 'american' => 'United States', 'americans' => 'United States',
        'california' => 'United States', 'new york' => 'United States', 'texas' => 'United States', 'florida' => 'United States',
        // Americas
        'the americas' => 'Americas', 'americas' => 'Americas', 'north america' => 'North America', 'north american' => 'North America',
        'south america' => 'South America', 'latin america' => 'Latin America', 'latin american' => 'Latin America', 'latam' => 'Latin America',
        'central america' => 'Central America', 'caribbean' => 'Caribbean', 'canada' => 'Canada', 'canadian' => 'Canada',
        'mexico' => 'Mexico', 'brazil' => 'Brazil', 'argentina' => 'Argentina', 'colombia' => 'Colombia', 'chile' => 'Chile', 'peru' => 'Peru',
        // Europe
        'united kingdom' => 'United Kingdom', 'uk' => 'United Kingdom', 'great britain' => 'United Kingdom', 'britain' => 'United Kingdom',
        'british' => 'United Kingdom', 'england' => 'United Kingdom', 'scotland' => 'United Kingdom', 'wales' => 'United Kingdom',
        'ireland' => 'Ireland', 'northern ireland' => 'United Kingdom',
        'europe' => 'Europe', 'european union' => 'Europe', 'european' => 'Europe', 'eu' => 'Europe', 'eea' => 'Europe', 'emea' => 'EMEA',
        'western europe' => 'Europe', 'eastern europe' => 'Europe', 'nordics' => 'Europe', 'scandinavia' => 'Europe',
        'germany' => 'Germany', 'france' => 'France', 'spain' => 'Spain', 'italy' => 'Italy', 'netherlands' => 'Netherlands',
        'belgium' => 'Belgium', 'switzerland' => 'Switzerland', 'austria' => 'Austria', 'sweden' => 'Sweden', 'norway' => 'Norway',
        'denmark' => 'Denmark', 'finland' => 'Finland', 'poland' => 'Poland', 'portugal' => 'Portugal', 'czech republic' => 'Czech Republic',
        'romania' => 'Romania', 'ukraine' => 'Ukraine', 'greece' => 'Greece', 'hungary' => 'Hungary',
        // Oceania
        'australia' => 'Australia', 'australian' => 'Australia', 'new zealand' => 'New Zealand', 'anz' => 'Australia / New Zealand', 'oceania' => 'Oceania',
        // Middle East and Africa
        'middle east' => 'Middle East', 'mena' => 'Middle East', 'gcc' => 'Middle East', 'uae' => 'United Arab Emirates',
        'united arab emirates' => 'United Arab Emirates', 'saudi arabia' => 'Saudi Arabia', 'qatar' => 'Qatar', 'israel' => 'Israel',
        'turkey' => 'Turkey', 'egypt' => 'Egypt', 'africa' => 'Africa', 'south africa' => 'South Africa', 'nigeria' => 'Nigeria', 'kenya' => 'Kenya',
        // Asian regions and countries without India
        'southeast asia' => 'Southeast Asia', 'south east asia' => 'Southeast Asia', 'east asia' => 'East Asia', 'eastern asia' => 'East Asia',
        'northeast asia' => 'East Asia', 'north east asia' => 'East Asia', 'central asia' => 'Central Asia', 'west asia' => 'West Asia',
        'western asia' => 'West Asia', 'philippines' => 'Philippines', 'singapore' => 'Singapore', 'japan' => 'Japan', 'china' => 'China',
        'hong kong' => 'Hong Kong', 'taiwan' => 'Taiwan', 'south korea' => 'South Korea', 'korea' => 'South Korea', 'vietnam' => 'Vietnam',
        'indonesia' => 'Indonesia', 'malaysia' => 'Malaysia', 'thailand' => 'Thailand', 'pakistan' => 'Pakistan', 'bangladesh' => 'Bangladesh',
        'sri lanka' => 'Sri Lanka', 'nepal' => 'Nepal',
    ];

    const US = ['United States'];
    const AMERICAS = ['United States', 'Americas', 'North America', 'South America', 'Latin America', 'Central America', 'Caribbean',
        'Canada', 'Mexico', 'Brazil', 'Argentina', 'Colombia', 'Chile', 'Peru'];

    const WORLDWIDE = [
        'anywhere in the world', 'no location restrictions', 'no location restriction', 'no restrictions', 'no restriction',
        'open to everyone', 'open to all', 'all countries', 'all locations', 'any location', 'any country', 'any region',
        'world wide', 'worldwide', 'globally', 'global', 'anywhere', 'international', 'everyone',
    ];

    /** Words that, next to a place, make it a hard requirement rather than a mention. */
    const RESTRICTIVE = ['only', 'must', 'exclusively', 'strictly', 'reside', 'residing', 'resident', 'residents', 'citizen', 'citizens',
        'located', 'physically', 'required', 'requires', 'require', 'restricted', 'limited'];

    /** Words that carry no meaning of their own in a location requirement. */
    const FILLER = ['only', 'remote', 'applicants', 'applicant', 'freelancers', 'freelancer', 'candidates', 'candidate', 'talent',
        'contractors', 'contractor', 'workers', 'people', 'must', 'be', 'located', 'location', 'locations', 'in', 'the', 'reside',
        'residing', 'resident', 'residents', 'based', 'physically', 'living', 'live', 'lives', 'from', 'within', 'citizens', 'citizen',
        'nationals', 'required', 'requirement', 'requires', 'require', 'is', 'are', 'a', 'an', 'and', 'or', 'of', 'to', 'who',
        'area', 'region', 'regions', 'country', 'countries', 'state', 'states', 'allowed', 'eligible', 'open', 'for', 'may', 'apply',
        'can', 'should', 'authorized', 'work', 'with', 'legally', 'permanent', 'hiring', 'this', 'job', 'role', 'position',
        'exclusively', 'strictly', 'restricted', 'limited', 'please', 'note', 'need', 'needs', 'we', 'currently', 'accepting', 'accept'];

    const CLIENT_NOUNS = '(?:client|clients|company|employer|business|startup|team|we|our company|our team|our business|our office|office|hq|headquarters)';

    /**
     * The freelancer-location text stored with a job, and where it came from.
     * Never the client's country.
     *
     * @param array $rawData the job's raw_data
     * @return array{0: ?string, 1: ?string} [text, source]
     */
    public static function extract(?array $rawData): array
    {
        foreach (self::SOURCES as $path) {
            $value = data_get($rawData ?? [], $path);
            $parts = [];
            if (is_array($value)) {
                // A list of countries, or {countries: [...], regions: [...]}: every non-empty string.
                array_walk_recursive($value, function ($leaf) use (&$parts) {
                    if (is_string($leaf) && trim($leaf) !== '') {
                        $parts[] = trim($leaf);
                    }
                });
            } elseif (is_string($value) && trim($value) !== '') {
                $parts = [trim($value)];
            }
            if ($parts) {
                return [mb_substr(implode(', ', array_unique($parts)), 0, 500), $path];
            }
        }

        return [null, null];
    }

    /**
     * @return array{eligibility: string, rule: string, reason: string, details: array}
     */
    public static function classify(?string $text): array
    {
        if ($text === null || trim($text) === '') {
            return self::result(self::ELIGIBLE, 'blank', []);
        }

        $normalized = self::normalize($text);
        $details = ['normalized' => $normalized];
        $india = '\b' . self::INDIA . '\b';

        // 2. Explicit India exclusion, in any position.
        $excluded = preg_match('/\b(?:except(?: for)?|excluding|excludes?|excluded|other than|but not|not including|outside(?: of)?|not (?:in|from|open to|available (?:in|to)|for)|non)\s+(?:\w+\s+){0,3}?' . self::INDIA . '\b/', $normalized)
            || preg_match('/\bno\s+(?:(?:applicants|freelancers|candidates|talent|agencies|residents|one|bids|proposals)\s+)?(?:(?:from|in)\s+)?' . self::INDIA . '\b/', $normalized)
            || preg_match('/' . $india . '\s+(?:(?:is|are|based|freelancers|applicants|residents|candidates|talent)\s+){0,2}(?:not|excluded|ineligible|prohibited|blocked|banned|disallowed)\b/', $normalized);
        $welcomed = preg_match('/\b(?:including|incl|includes|include|open to|welcome to|allowed from|accepted from)\s+(?:\w+\s+){0,2}?' . self::INDIA . '\b/', $normalized)
            || preg_match('/' . $india . '\s+(?:(?:is|are|freelancers|applicants|candidates|talent)\s+){0,2}(?:welcome|welcomed|allowed|ok|okay|accepted|eligible|fine)\b/', $normalized);

        if ($excluded) {
            return $welcomed
                ? self::result(self::UNKNOWN, 'india_conflict', $details)
                : self::result(self::RESTRICTED, 'india_excluded', $details);
        }
        // 3. India named, not excluded.
        if (preg_match('/' . $india . '/', $normalized)) {
            return self::result(self::ELIGIBLE, 'india_included', $details);
        }

        // The client's own location is not a requirement on the freelancer.
        [$rest, $client] = self::withoutClientLocation($normalized);
        if ($client) {
            $details['client_location'] = $client;
        }

        [$rest, $worldwide] = self::consume($rest, self::WORLDWIDE, 'WORLDWIDE');
        // Longest first, so "southeast asia" (no India) is taken before "asia" (India).
        [$rest, $places] = self::consume($rest, array_keys(self::PLACES), 'PLACE');
        [$rest, $regions] = self::consume($rest, self::INDIA_REGIONS, 'REGION');
        $places = array_values(array_unique(array_map(fn($p) => self::PLACES[$p], $places)));
        if ($places) {
            $details['places'] = $places;
        }

        $words = array_values(array_filter(explode(' ', $rest), fn($w) => $w !== '' && !in_array($w, ['WORLDWIDE', 'PLACE', 'REGION'], true)));
        $restrictive = (bool) array_intersect($words, self::RESTRICTIVE);
        $unexplained = array_values(array_diff($words, self::FILLER));
        if ($unexplained) {
            $details['unrecognized_words'] = array_slice($unexplained, 0, 10);
        }

        // 4. Worldwide.
        if ($worldwide) {
            return $places && $restrictive
                ? self::result(self::UNKNOWN, 'worldwide_conflict', $details)
                : self::result(self::ELIGIBLE, 'worldwide', $details);
        }
        // 5. A region that contains India.
        if ($regions) {
            return $places && $restrictive
                ? self::result(self::UNKNOWN, 'india_region_conflict', $details)
                : self::result(self::ELIGIBLE, 'india_region', $details);
        }
        // 6. Only places without India, and nothing we do not understand ("US preferred" stays unknown).
        if ($places && !$unexplained) {
            $rule = !array_diff($places, self::US) ? 'restricted_us'
                : (!array_diff($places, self::AMERICAS) ? 'restricted_americas' : 'restricted_region');

            return self::result(self::RESTRICTED, $rule, $details);
        }
        if (!$places && !$unexplained && !$restrictive) {
            // 7 / 8. Nothing left once the client's location is set aside, or just "Remote".
            return self::result(self::ELIGIBLE, $client ? 'client_location_only' : 'no_restriction_stated', $details);
        }

        return self::result(self::UNKNOWN, 'unrecognized', $details);
    }

    /** Lower case, one space between words, abbreviations spelled one way. "US" (capitals) is the country, "us" is a pronoun. */
    public static function normalize(string $text): string
    {
        $text = preg_replace('/\bU\.?\s?S\.?(?:\s?A\.?)?(?=[^A-Za-z]|$)/', ' usa ', $text);
        $text = preg_replace('/\bU\.K\.?/i', ' uk ', $text);
        $text = preg_replace('/\bE\.U\.?/i', ' eu ', $text);
        $text = mb_strtolower($text);
        $text = preg_replace('/\bus(?=\s*(?:-|\s)\s*(?:only|based|residents?|citizens?|applicants|freelancers)\b)/', 'usa', $text);
        $text = preg_replace('/\b(remote|in|within)\s*-?\s*us\s*$/', '$1 usa', $text);
        $text = str_replace('&', ' and ', $text);
        $text = preg_replace('/[^a-z0-9]+/', ' ', $text);

        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /** @return array{0: string, 1: ?string} text without client-location phrases, and the client location found */
    private static function withoutClientLocation(string $text): array
    {
        $place = '(' . implode('|', array_map(fn($p) => preg_quote($p, '/'), self::byLength(array_merge(array_keys(self::PLACES), self::INDIA_REGIONS)))) . ')';
        $patterns = [
            '/\b(?:the\s+)?' . self::CLIENT_NOUNS . '\s+(?:is\s+|are\s+)?(?:based|located|headquartered|situated|from|in)\s+(?:in\s+)?(?:the\s+)?' . $place . '\b/',
            '/\b' . $place . '\s+based\s+' . self::CLIENT_NOUNS . '\b/',
            '/\b' . self::CLIENT_NOUNS . '\s+(?:location|country)\s+' . $place . '\b/',
        ];
        $found = null;
        foreach ($patterns as $pattern) {
            $text = preg_replace_callback($pattern, function ($m) use (&$found) {
                $found = $found ?? (self::PLACES[$m[1]] ?? $m[1]);
                return ' ';
            }, $text);
        }

        return [trim(preg_replace('/\s+/', ' ', $text)), $found];
    }

    /** Replace each phrase (whole words, longest first) with a marker. @return array{0: string, 1: string[]} */
    private static function consume(string $text, array $phrases, string $marker): array
    {
        $found = [];
        foreach (self::byLength($phrases) as $phrase) {
            $text = preg_replace_callback('/\b' . preg_quote($phrase, '/') . '\b/', function () use (&$found, $phrase, $marker) {
                $found[] = $phrase;
                return $marker;
            }, $text);
        }

        return [$text, $found];
    }

    private static function byLength(array $phrases): array
    {
        usort($phrases, fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        return $phrases;
    }

    private static function result(string $eligibility, string $rule, array $details): array
    {
        $reason = $eligibility === self::RESTRICTED ? self::SKIP_REASON
            : ($eligibility === self::UNKNOWN ? self::UNKNOWN_REASON : self::REASONS[$rule]);

        return ['eligibility' => $eligibility, 'rule' => $rule, 'reason' => $reason, 'details' => $details];
    }

    /** One line for lists and logs: "Restricted — United States only (rule restricted_us)". */
    public static function describe(UpworkOpportunity $o): string
    {
        if (!$o->location_eligibility) {
            return 'Not classified yet';
        }

        return self::LABELS[$o->location_eligibility] . ' — ' . ($o->location_text !== null ? '"' . $o->location_text . '"' : 'no location given')
            . ($o->location_override ? ' (manual override; rules said ' . self::LABELS[$o->location_auto_eligibility] . ')' : " (rule {$o->location_rule})");
    }
}

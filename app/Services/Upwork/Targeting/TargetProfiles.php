<?php

namespace App\Services\Upwork\Targeting;

use App\Models\UpworkSetting;
use App\Models\UpworkTechnology;
use Illuminate\Support\Str;

/**
 * The profiles Code4Each bids as: the agency profile and any number of
 * individual freelancer profiles. These are proposal-writing personas inside
 * the PMS only. They are deliberately NOT linked to Upwork accounts: the PMS
 * never selects, switches or submits from an Upwork account. A person picks
 * the account on Upwork when they send the proposal themselves.
 *
 * Verified experience: the agency cites the approved agency profile text and
 * PROVEN catalog technologies; an individual cites only their own summary and
 * the catalog technologies recorded for them.
 */
class TargetProfiles
{
    const TRACK_AGENCY = 'agency';
    const TRACK_INDIVIDUAL = 'individual';
    const TRACK_BOTH = 'both';

    const TRACKS = [
        self::TRACK_AGENCY => 'Agency',
        self::TRACK_INDIVIDUAL => 'Individual',
        self::TRACK_BOTH => 'Either',
    ];

    const SETTING = 'targeting.individual_profiles';
    const MAX_PROFILES = 20;

    /** Generic individual profile, used when none is configured. */
    const GENERIC_INDIVIDUAL = 'individual';

    /**
     * Individual freelancer profiles: [key => [key, name, summary, technologies (slugs)]].
     */
    public static function individuals(): array
    {
        $out = [];
        foreach ((array) UpworkSetting::get(self::SETTING) as $row) {
            if (!is_array($row) || empty($row['key']) || empty($row['name'])) {
                continue;
            }
            $out[$row['key']] = [
                'key' => (string) $row['key'],
                'name' => (string) $row['name'],
                'summary' => trim((string) ($row['summary'] ?? '')),
                'technologies' => array_values(array_filter((array) ($row['technologies'] ?? []), 'is_string')),
            ];
        }

        return $out;
    }

    /**
     * Every profile a reviewer can choose: value => [value, track, label, summary, technologies].
     * "agency" first, then "individual:<key>" per configured freelancer, or a
     * generic "individual" when none is configured.
     */
    public static function options(): array
    {
        $options = [self::TRACK_AGENCY => [
            'value' => self::TRACK_AGENCY, 'track' => self::TRACK_AGENCY, 'label' => 'Agency profile (Code4Each)',
            'name' => 'Code4Each', 'summary' => trim((string) UpworkSetting::get('proposals.profile')), 'technologies' => null,
        ]];

        $individuals = self::individuals();
        foreach ($individuals as $key => $p) {
            $options["individual:{$key}"] = [
                'value' => "individual:{$key}", 'track' => self::TRACK_INDIVIDUAL, 'label' => "Individual: {$p['name']}",
                'name' => $p['name'], 'summary' => $p['summary'], 'technologies' => $p['technologies'],
            ];
        }
        if (!$individuals) {
            $options[self::GENERIC_INDIVIDUAL] = [
                'value' => self::GENERIC_INDIVIDUAL, 'track' => self::TRACK_INDIVIDUAL, 'label' => 'Individual freelancer (no profile configured)',
                'name' => null, 'summary' => '', 'technologies' => [],
            ];
        }

        return $options;
    }

    public static function find(?string $value): ?array
    {
        return $value !== null ? (self::options()[$value] ?? null) : null;
    }

    public static function trackOf(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value === self::TRACK_AGENCY ? self::TRACK_AGENCY : (str_starts_with($value, self::TRACK_INDIVIDUAL) ? self::TRACK_INDIVIDUAL : null);
    }

    /** Human label for a stored selection, even if that profile was removed since. */
    public static function label(?string $value): string
    {
        if ($value === null) {
            return '—';
        }
        if ($option = self::find($value)) {
            return $option['label'];
        }

        return str_starts_with($value, 'individual:') ? 'Individual: ' . substr($value, 11) . ' (profile removed)' : $value;
    }

    /**
     * Normalize admin input into the stored list. Unknown technology slugs are
     * dropped; keys are stable slugs of the name unless given.
     *
     * @param array $rows [[name, key?, summary, technologies[], remove?]]
     */
    public static function normalize(array $rows): array
    {
        $known = UpworkTechnology::pluck('slug')->all();
        $out = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '' || !empty($row['remove'])) {
                continue;
            }
            $key = Str::slug((string) (($row['key'] ?? '') !== '' ? $row['key'] : $name));
            if ($key === '' || isset($out[$key])) {
                continue;
            }
            $out[$key] = [
                'key' => $key,
                'name' => mb_substr($name, 0, 100),
                'summary' => mb_substr(trim((string) ($row['summary'] ?? '')), 0, 4000),
                'technologies' => array_values(array_intersect(array_unique((array) ($row['technologies'] ?? [])), $known)),
            ];
        }

        return array_slice(array_values($out), 0, self::MAX_PROFILES);
    }
}

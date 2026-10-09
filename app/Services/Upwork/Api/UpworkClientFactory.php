<?php

namespace App\Services\Upwork\Api;

use App\Models\UpworkSetting;
use App\Services\Upwork\Api\Contracts\UpworkClientInterface;

/** Picks the Upwork client from settings. The fixture client never runs in production. */
class UpworkClientFactory
{
    public static function make(?string $name = null): UpworkClientInterface
    {
        $name = $name ?? (string) UpworkSetting::get('discovery.client');

        if ($name === 'mcp') {
            return new McpUpworkClient();
        }
        if ($name === 'fixture' && !app()->environment('production')) {
            return new FixtureUpworkClient(null, config('upwork.api.fixture_mode'));
        }

        return new GraphQlUpworkClient();
    }

    public static function available(): array
    {
        return app()->environment('production') ? ['graphql', 'mcp'] : ['graphql', 'mcp', 'fixture'];
    }
}

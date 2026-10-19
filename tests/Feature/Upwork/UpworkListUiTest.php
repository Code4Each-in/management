<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkOpportunity;

/**
 * Opportunities and Radar list controls: records per page, filter/sort
 * preservation, active-filter chips, empty states and bulk selection labels.
 */
class UpworkListUiTest extends UpworkTestCase
{
    private function rows(string $html): int
    {
        return substr_count($html, 'class="uw-job-title"');
    }

    private function seedJobs(int $count, array $attributes = []): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $this->opportunity(array_merge(['title' => sprintf('Listed job %03d', $i), 'score' => 50 + ($i % 50)], $attributes));
        }
    }

    public function test_every_page_size_drives_the_real_pagination(): void
    {
        config(['upwork.per_page' => 25]);
        $this->seedJobs(137);
        $bde = $this->bde();

        foreach ([10, 25, 50, 100] as $size) {
            $html = $this->actingAs($bde)->get(route('upwork.opportunities.index', ['per_page' => $size]))->assertOk()
                ->assertSee("Showing 1–{$size} of 137 opportunities")
                ->assertSee('<option value="' . $size . '" selected>', false)
                ->getContent();
            $this->assertSame($size, $this->rows($html), "per_page={$size} lists {$size} rows");
        }

        // Second page of 25, and the last (partial) page of 50.
        $this->actingAs($bde)->get(route('upwork.opportunities.index', ['per_page' => 25, 'page' => 2]))->assertOk()
            ->assertSee('Showing 26–50 of 137 opportunities');
        $html = $this->actingAs($bde)->get(route('upwork.opportunities.index', ['per_page' => 50, 'page' => 3]))->assertOk()
            ->assertSee('Showing 101–137 of 137 opportunities')->getContent();
        $this->assertSame(37, $this->rows($html));
    }

    public function test_page_size_is_validated_and_remembered(): void
    {
        config(['upwork.per_page' => 25]);
        $this->seedJobs(60);
        $bde = $this->bde();

        foreach (['7', '1000', 'abc', '-10', '25.5'] as $bad) {
            $this->actingAs($bde)->get(route('upwork.opportunities.index', ['per_page' => $bad]))->assertOk()
                ->assertSee('Showing 1–25 of 60');
        }

        // A valid choice sticks for later visits without ?per_page=, on both Upwork lists.
        $this->actingAs($bde)->get(route('upwork.opportunities.index', ['per_page' => 50]))->assertOk()->assertSessionHas('upwork.per_page', 50);
        $this->actingAs($bde)->get(route('upwork.opportunities.index'))->assertOk()->assertSee('Showing 1–50 of 60');
        $this->actingAs($bde)->get(route('upwork.radar'))->assertOk()->assertSee('Showing 1–50 of 60 opportunities');
        // An invalid value does not overwrite it.
        $this->actingAs($bde)->get(route('upwork.opportunities.index', ['per_page' => 3]))->assertOk()->assertSee('Showing 1–50 of 60');
    }

    public function test_a_page_past_the_end_goes_to_the_last_page_and_keeps_filters(): void
    {
        $this->seedJobs(30, ['recommendation' => 'apply']);
        $bde = $this->bde();

        $response = $this->actingAs($bde)->get(route('upwork.opportunities.index', ['recommendation' => 'apply', 'sort' => 'posted_at', 'per_page' => 25, 'page' => 9]));
        $response->assertRedirect();
        $target = $response->headers->get('Location');
        $this->assertStringContainsString('page=2', $target);
        $this->assertStringContainsString('recommendation=apply', $target);
        $this->assertStringContainsString('sort=posted_at', $target);

        // No results at all: page 9 simply shows the empty state (nothing to redirect to).
        $this->actingAs($bde)->get(route('upwork.opportunities.index', ['search' => 'nothing-matches', 'page' => 9]))->assertOk()
            ->assertSee('No opportunities match these filters.')->assertSee('No opportunities');
    }

    public function test_filters_and_sorting_survive_paging_and_page_size_changes(): void
    {
        $this->seedJobs(30, ['recommendation' => 'apply']);
        $html = $this->actingAs($this->bde())
            ->get(route('upwork.opportunities.index', ['recommendation' => 'apply', 'sort' => 'posted_at', 'min_score' => 10, 'per_page' => 10]))
            ->assertOk()->getContent();

        // Page links carry filters, sorting and size.
        $this->assertStringContainsString('recommendation=apply&amp;sort=posted_at&amp;min_score=10&amp;per_page=10&amp;page=2', $html);
        // The page-size form re-sends the applied filters and sorting, but not the page.
        $perPageForm = substr($html, strpos($html, 'class="uw-perpage"'));
        $perPageForm = substr($perPageForm, 0, strpos($perPageForm, '</form>'));
        $this->assertStringContainsString('name="recommendation" value="apply"', $perPageForm);
        $this->assertStringContainsString('name="sort" value="posted_at"', $perPageForm);
        $this->assertStringContainsString('name="min_score" value="10"', $perPageForm);
        $this->assertStringNotContainsString('name="page"', $perPageForm);
        // Applying new filters keeps the chosen sort.
        $this->assertStringContainsString('<input type="hidden" name="sort" value="posted_at">', $html);
        // "Select all matching" sends the filter only, never paging or sorting.
        $this->assertStringContainsString('name="filter[recommendation]" value="apply"', $html);
        $this->assertStringNotContainsString('name="filter[sort]"', $html);
        $this->assertStringNotContainsString('name="filter[per_page]"', $html);
        $this->assertStringNotContainsString('name="filter[page]"', $html);
    }

    public function test_unknown_or_array_query_parameters_do_not_break_the_list(): void
    {
        $this->opportunity(['title' => 'Robust job']);

        $html = $this->actingAs($this->bde())->get(route('upwork.opportunities.index') . '?foo[]=1&search[]=x&utm=abc&page=1')
            ->assertOk()->assertSee('Robust job')->getContent();
        $this->assertStringNotContainsString('name="foo', $html);
        $this->assertStringNotContainsString('name="utm"', $html);
        $this->assertStringNotContainsString('name="filter[utm]"', $html);
    }

    public function test_active_filter_chips_remove_one_filter_and_reset_keeps_the_view(): void
    {
        $this->opportunity(['title' => 'Chip job', 'recommendation' => 'apply', 'score' => 90]);
        $html = $this->actingAs($this->bde())
            ->get(route('upwork.opportunities.index', ['view' => 'apply', 'search' => 'Chip', 'min_score' => 80]))
            ->assertOk()->assertSee('Active filters:')->assertSee('Search: “Chip”', false)->assertSee('Score ≥ 80')
            ->assertSee('Chip job')->getContent();

        // Removing the score chip keeps the view and the search.
        $this->assertStringContainsString('href="' . e(route('upwork.opportunities.index', ['view' => 'apply', 'search' => 'Chip'])) . '" class="uw-chip"', $html);
        // Reset clears filters but stays on the current tab.
        $this->assertStringContainsString('href="' . e(route('upwork.opportunities.index', ['view' => 'apply'])) . '" class="btn btn-sm btn-outline-secondary">Reset filters', $html);
    }

    public function test_advanced_filters_open_when_used_and_unknown_values_are_ignored(): void
    {
        $this->opportunity(['title' => 'Agency fit job'])->forceFill(['target_profile' => 'agency'])->save();
        $bde = $this->bde();

        $this->actingAs($bde)->get(route('upwork.opportunities.index'))->assertOk()
            ->assertSee('class="collapse " id="uw-advanced"', false);
        $this->actingAs($bde)->get(route('upwork.opportunities.index', ['target' => 'agency']))->assertOk()
            ->assertSee('class="collapse show" id="uw-advanced"', false)->assertSee('Best fit: Fits agency (incl. either)')->assertSee('Agency fit job');
        // An invalid value is dropped: no chip, full list.
        $this->actingAs($bde)->get(route('upwork.opportunities.index', ['status' => 'bogus']))->assertOk()
            ->assertDontSee('Active filters:')->assertSee('Agency fit job');
    }

    public function test_bulk_bar_separates_this_page_from_all_matching(): void
    {
        config(['upwork.per_page' => 25]);
        $this->seedJobs(30);
        $this->actingAs($this->bde())->get(route('upwork.opportunities.index', ['per_page' => 10]))->assertOk()
            ->assertSee('Select current page (10)')
            ->assertSee('Select all 30 jobs matching this filter')
            ->assertSee('name="expected" value="30"', false);

        // Without Upwork access the list itself is refused (archive shares viewAny's access rule).
        $this->actingAs($this->developer())->get(route('upwork.opportunities.index'))->assertForbidden();
    }

    public function test_radar_top_list_is_paginated_with_the_same_page_sizes(): void
    {
        config(['upwork.per_page' => 25]);
        $this->seedJobs(30);
        $this->opportunity(['title' => 'Rejected radar job', 'status' => UpworkOpportunity::STATUS_REJECTED, 'score' => 99]);
        $bde = $this->bde();

        $html = $this->actingAs($bde)->get(route('upwork.radar', ['per_page' => 10]))->assertOk()
            ->assertSee('Showing 1–10 of 30 opportunities')->assertSee('Records per page')
            ->assertDontSee('Rejected radar job')->getContent();
        $this->assertSame(10, $this->rows($html));
        // Range and size survive paging; the page-size form keeps the range.
        $this->assertStringContainsString('per_page=10&amp;page=2', $html);
        $this->actingAs($bde)->get(route('upwork.radar', ['range' => '7d', 'per_page' => 10, 'page' => 3]))->assertOk()
            ->assertSee('Showing 21–30 of 30 opportunities')->assertSee('name="range" value="7d"', false);
        $this->actingAs($bde)->get(route('upwork.radar', ['per_page' => 10, 'page' => 99]))->assertRedirect();
    }

    public function test_query_count_does_not_grow_with_page_size(): void
    {
        $this->seedJobs(100);
        $bde = $this->bde();
        $count = function (string $route, int $size) use ($bde) {
            $this->actingAs($bde)->get(route($route, ['per_page' => 10]))->assertOk(); // warm up session, auth and caches
            \Illuminate\Support\Facades\DB::flushQueryLog();
            \Illuminate\Support\Facades\DB::enableQueryLog();
            $this->actingAs($bde)->get(route($route, ['per_page' => $size]))->assertOk();
            $n = count(\Illuminate\Support\Facades\DB::getQueryLog());
            \Illuminate\Support\Facades\DB::disableQueryLog();

            return $n;
        };

        $this->assertSame($count('upwork.opportunities.index', 10), $count('upwork.opportunities.index', 100), 'no per-row queries on Opportunities');
        $this->assertSame($count('upwork.radar', 10), $count('upwork.radar', 100), 'no per-row queries on Radar');
    }

    public function test_radar_offers_discovery_actions_by_permission(): void
    {
        $this->actingAs($this->bde())->get(route('upwork.radar'))->assertOk()
            ->assertDontSee(route('upwork.config.discovery.run'), false);
        $this->actingAs($this->superAdmin())->get(route('upwork.radar'))->assertOk()
            ->assertSee(route('upwork.config.discovery.run'), false)->assertSee('Run discovery now')
            ->assertSee(route('upwork.config.profiles.index'), false);
    }
}

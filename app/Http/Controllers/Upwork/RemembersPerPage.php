<?php

namespace App\Http\Controllers\Upwork;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * "Records per page" for the Upwork lists (Opportunities, Radar). A valid
 * ?per_page= choice is remembered in the session, so it survives refreshes
 * and moving between the two pages; anything else falls back to the saved
 * choice, then to config('upwork.per_page').
 */
trait RemembersPerPage
{
    public static function perPageOptions(): array
    {
        return [10, 25, 50, 100];
    }

    protected function perPage(Request $request): int
    {
        $chosen = filter_var($request->query('per_page'), FILTER_VALIDATE_INT);
        if (in_array($chosen, self::perPageOptions(), true)) {
            $request->session()->put('upwork.per_page', $chosen);

            return $chosen;
        }
        $saved = $request->session()->get('upwork.per_page');

        return in_array($saved, self::perPageOptions(), true) ? $saved : (int) config('upwork.per_page', 25);
    }

    /** A page past the end (e.g. after a bigger page size or archiving) goes to the last real page. */
    protected function pastLastPage(LengthAwarePaginator $page): ?string
    {
        return $page->isEmpty() && $page->total() > 0 && $page->currentPage() > $page->lastPage()
            ? $page->url($page->lastPage())
            : null;
    }
}

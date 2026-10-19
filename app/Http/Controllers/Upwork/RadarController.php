<?php

namespace App\Http\Controllers\Upwork;

use App\Http\Controllers\Controller;
use App\Models\UpworkOpportunity;
use App\Services\Upwork\RadarService;
use Illuminate\Http\Request;

/** Upwork Opportunity Radar dashboard. Same access as the opportunity list. */
class RadarController extends Controller
{
    use RemembersPerPage;

    public function index(Request $request, RadarService $radar)
    {
        $this->authorize('viewAny', UpworkOpportunity::class);

        $range = array_key_exists($request->query('range'), RadarService::RANGES) ? $request->query('range') : 'today';

        [$from, $to] = RadarService::window($range);

        // The period's top opportunities, paginated (the Radar's only list).
        $top = $radar->topQuery($range)->paginate($this->perPage($request))->withQueryString();
        if ($last = $this->pastLastPage($top)) {
            return redirect()->to($last);
        }

        return view('upwork.radar', [
            'data' => $radar->radar($range, false),
            'top' => $top,
            'perPageOptions' => self::perPageOptions(),
            'ranges' => RadarService::RANGES,
            'metrics' => app(\App\Services\Upwork\MetricsService::class)->summary($from, $to),
            'openAlerts' => \App\Services\Upwork\Notifications\AlertService::open(),
        ]);
    }
}

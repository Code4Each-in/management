<?php

namespace App\Http\Controllers;

use App\Models\UpworkClient;
use App\Models\UpworkOpportunity;
use App\Services\Upwork\Clients\ClientIntelligenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/** Client intelligence pages (read-only). Same access as Upwork Opportunities. */
class UpworkClientController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', UpworkOpportunity::class);

        $filters = Validator::make($request->query(), [
            'search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in(['last_seen', 'jobs', 'spend'])],
        ])->valid();

        $query = UpworkClient::withCount([
            'opportunities',
            'opportunities as approved_count' => fn($q) => $q->whereNotNull('approved_at'),
            'opportunities as won_count' => fn($q) => $q->where('status', UpworkOpportunity::STATUS_WON),
        ])->when($filters['search'] ?? null, function ($q, $search) {
            $q->where(fn($qq) => $qq->where('external_id', 'like', "%{$search}%")->orWhere('country', 'like', "%{$search}%"));
        });

        $sort = $filters['sort'] ?? 'last_seen';
        if ($sort === 'jobs') {
            $query->orderByDesc('opportunities_count');
        } elseif ($sort === 'spend') {
            $query->orderByRaw('total_spent IS NULL')->orderByDesc('total_spent');
        } else {
            $query->orderByDesc('last_seen_at');
        }

        $clients = $query->orderByDesc('id')->paginate(25)->withQueryString();

        return view('upwork.clients.index', compact('clients', 'filters', 'sort'));
    }

    public function show(UpworkClient $client, ClientIntelligenceService $intelligence)
    {
        $this->authorize('viewAny', UpworkOpportunity::class);

        $history = $intelligence->history($client);
        $snapshots = $client->snapshots()->get();

        return view('upwork.clients.show', compact('client', 'history', 'snapshots'));
    }
}

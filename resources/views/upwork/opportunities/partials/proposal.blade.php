@php $M = \App\Models\UpworkOpportunity::class; @endphp
<div style="{{ $card }}">
  <div class="d-flex justify-content-between align-items-center">
    <div style="{{ $h }} margin-bottom:0;">Proposal draft</div>
    @if($o->proposal_approved_at)
      <span class="badge" style="background:#d1e7dd; color:#0f5132;">Approved {{ $o->proposal_approved_at->format('d M Y H:i') }}{{ $o->proposalApprover ? ' by ' . $o->proposalApprover->first_name : '' }}</span>
    @endif
  </div>
  <div class="small text-muted mb-3" style="font-size:0.75rem;">Drafts stay in the PMS. Submission happens manually on Upwork by a human.</div>

  @if($o->status === $M::STATUS_APPROVED)
    @can('manageProposal', $o)
      <form method="POST" action="{{ route('upwork.opportunities.proposal.generate', $o) }}" class="m-0">
        @csrf
        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-file-earmark-text"></i> Generate proposal draft</button>
      </form>
    @endcan

  @elseif($o->status === $M::STATUS_PROPOSAL_DRAFT)
    @can('manageProposal', $o)
      <form method="POST" action="{{ route('upwork.opportunities.proposal.update', $o) }}">
        @csrf
        @method('PUT')
        <textarea name="proposal_draft" rows="12" maxlength="20000" class="form-control mb-2" style="font-size:0.875rem;" required>{{ old('proposal_draft', $o->proposal_draft) }}</textarea>
        <div class="row g-2 mb-2">
          <div class="col-sm-6">
            <label class="form-label small mb-1" style="font-weight:600;">Proposed rate ($)</label>
            <input type="number" step="0.01" min="0" name="proposed_rate" value="{{ old('proposed_rate', $o->proposed_rate) }}" class="form-control form-control-sm">
          </div>
          <div class="col-sm-6">
            <label class="form-label small mb-1" style="font-weight:600;">Estimated hours</label>
            <input type="number" step="0.5" min="0" name="estimated_hours" value="{{ old('estimated_hours', $o->estimated_hours) }}" class="form-control form-control-sm">
          </div>
        </div>
        @foreach(($o->screening_answers ?? []) as $i => $qa)
          <div class="mb-2">
            <input type="hidden" name="screening_answers[{{ $i }}][question]" value="{{ $qa['question'] ?? '' }}">
            <label class="form-label small mb-1" style="font-weight:600;">{{ $qa['question'] ?? 'Screening question' }}</label>
            <textarea name="screening_answers[{{ $i }}][answer]" rows="3" class="form-control form-control-sm">{{ old("screening_answers.$i.answer", $qa['answer'] ?? '') }}</textarea>
          </div>
        @endforeach
        <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-save"></i> Save draft</button>
        @if($o->proposal_edited_at)
          <span class="small text-muted ms-2">Last edited {{ $o->proposal_edited_at->format('d M Y H:i') }}{{ $o->proposalEditor ? ' by ' . $o->proposalEditor->first_name : '' }}</span>
        @endif
      </form>

      <div class="d-flex gap-2 mt-3 pt-3 border-top flex-wrap">
        @if(!$o->proposal_approved_at)
          <form method="POST" action="{{ route('upwork.opportunities.proposal.approve', $o) }}" class="m-0">
            @csrf
            <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check2-circle"></i> Approve draft</button>
          </form>
        @else
          <form method="POST" action="{{ route('upwork.opportunities.proposal.submitted', $o) }}" class="m-0">
            @csrf
            <button type="submit" class="btn btn-dark btn-sm" onclick="return confirm('Confirm you have already submitted this proposal on Upwork yourself. The PMS does not submit anything.');">
              <i class="bi bi-send-check"></i> Mark as submitted (done manually on Upwork)
            </button>
          </form>
        @endif
      </div>
    @else
      <div style="white-space:pre-wrap; font-size:0.875rem;">{{ $o->proposal_draft }}</div>
    @endcan

  @elseif($o->proposal_draft)
    <div style="white-space:pre-wrap; font-size:0.875rem; background:#fafafa; border:1px solid #f3f4f6; border-radius:0.375rem; padding:0.75rem;">{{ $o->proposal_draft }}</div>
    <div class="small text-muted mt-2">
      Rate: {{ $o->proposed_rate !== null ? '$' . number_format($o->proposed_rate, 2) : '—' }} ·
      Est. hours: {{ $o->estimated_hours ?? '—' }}
      @if($o->submitted_at) · Submitted {{ $o->submitted_at->format('d M Y H:i') }}@endif
    </div>
    @foreach(($o->screening_answers ?? []) as $qa)
      <div class="mt-2 small"><strong>{{ $qa['question'] ?? '' }}</strong><div style="white-space:pre-wrap;">{{ $qa['answer'] ?? '' }}</div></div>
    @endforeach
  @else
    <div class="text-muted small">Approve the opportunity to start a proposal draft.</div>
  @endif

  @if(in_array($o->status, [$M::STATUS_SUBMITTED, $M::STATUS_CLIENT_RESPONSE], true))
    @can('manageProposal', $o)
      <form method="POST" action="{{ route('upwork.opportunities.outcome', $o) }}" class="d-flex gap-2 align-items-center mt-3 pt-3 border-top flex-wrap">
        @csrf
        <select name="status" class="form-select form-select-sm" style="width:auto;" required>
          @foreach($M::TRANSITIONS[$o->status] as $next)
            <option value="{{ $next }}">{{ $M::STATUSES[$next] }}</option>
          @endforeach
        </select>
        <input type="text" name="note" maxlength="2000" placeholder="Note (optional)" class="form-control form-control-sm" style="max-width:280px;">
        <button type="submit" class="btn btn-outline-primary btn-sm">Update status</button>
      </form>
    @endcan
  @endif
</div>

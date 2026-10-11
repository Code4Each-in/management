<div class="modal fade" id="upworkRejectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" action="" id="upworkRejectForm">
      @csrf
      <div class="modal-content border rounded-3 p-4">
        <h5 class="mb-1" style="font-weight:700;">Reject opportunity</h5>
        <p class="text-muted small mb-3" id="upworkRejectTitle"></p>
        <label for="rejection_reason" class="form-label small" style="font-weight:600;">Reason (optional)</label>
        <textarea name="rejection_reason" id="rejection_reason" rows="3" maxlength="2000" class="form-control form-control-sm"
                  placeholder="e.g. budget too low, outside our stack, client already hired…"></textarea>
        <div class="d-flex justify-content-end gap-2 mt-3">
          <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-sm btn-danger">Reject</button>
        </div>
      </div>
    </form>
  </div>
</div>
<script>
document.addEventListener('click', function (e) {
  const btn = e.target.closest('.js-reject');
  if (!btn) return;
  document.getElementById('upworkRejectForm').action = btn.dataset.action;
  document.getElementById('upworkRejectTitle').textContent = btn.dataset.title || '';
  document.getElementById('rejection_reason').value = '';
  bootstrap.Modal.getOrCreateInstance(document.getElementById('upworkRejectModal')).show();
});
</script>

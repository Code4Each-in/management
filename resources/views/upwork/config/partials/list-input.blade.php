{{-- Editable list: one input per entry with add/remove. Expects $name, $label, $values (array|null), optional $help, $placeholder, $disabled. --}}
@php $values = old($name, $values ?? []) ?: []; @endphp
<div class="mb-3 uw-list" data-name="{{ $name }}">
  <label class="form-label small fw-semibold mb-1">{{ $label }}</label>
  @if(!empty($help))<div class="uw-help mb-1">{{ $help }}</div>@endif
  <div class="uw-list-items">
    @foreach($values as $value)
      <div class="input-group input-group-sm mb-1">
        <input type="text" name="{{ $name }}[]" value="{{ $value }}" class="form-control" maxlength="100" {{ ($disabled ?? false) ? "disabled" : "" }}>
        @unless($disabled ?? false)<button type="button" class="btn btn-outline-secondary uw-list-remove" title="Remove">&times;</button>@endunless
      </div>
    @endforeach
  </div>
  @unless($disabled ?? false)
    <div class="input-group input-group-sm" style="max-width:420px;">
      <input type="text" class="form-control uw-list-new" placeholder="{{ $placeholder ?? 'Add an entry and press Enter' }}" maxlength="100">
      <button type="button" class="btn btn-outline-primary uw-list-add">Add</button>
    </div>
  @endunless
</div>
@once
<script>
document.addEventListener('DOMContentLoaded', function () {
  function addItem(list, value) {
    value = value.trim();
    if (!value) return;
    var row = document.createElement('div');
    row.className = 'input-group input-group-sm mb-1';
    var input = document.createElement('input');
    input.type = 'text'; input.name = list.dataset.name + '[]'; input.value = value; input.className = 'form-control'; input.maxLength = 100;
    var btn = document.createElement('button');
    btn.type = 'button'; btn.className = 'btn btn-outline-secondary uw-list-remove'; btn.title = 'Remove'; btn.innerHTML = '&times;';
    row.appendChild(input); row.appendChild(btn);
    list.querySelector('.uw-list-items').appendChild(row);
  }
  document.querySelectorAll('.uw-list').forEach(function (list) {
    var input = list.querySelector('.uw-list-new');
    if (!input) return;
    list.querySelector('.uw-list-add').addEventListener('click', function () { addItem(list, input.value); input.value = ''; input.focus(); });
    input.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); addItem(list, input.value); input.value = ''; } });
  });
  document.addEventListener('click', function (e) {
    if (e.target.classList.contains('uw-list-remove')) { e.target.closest('.input-group').remove(); }
  });
});
</script>
@endonce

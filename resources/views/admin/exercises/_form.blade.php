@php($editing = isset($exercise))
<div class="ad-form-grid">
  <label><span class="ad-label">Exercise name</span><input name="name" required maxlength="160" value="{{ old('name', $exercise->name ?? '') }}"></label>
  <label><span class="ad-label">Muscle group</span><input name="muscle_group" maxlength="80" value="{{ old('muscle_group', $exercise->muscle_group ?? '') }}" placeholder="Chest, Back, Biceps…"></label>
  <label class="ad-field-wide"><span class="ad-label">Ranking method</span>
    <select name="scoring_type" required data-scoring-type>
      @foreach($scoringTypes as $value => $label)<option value="{{ $value }}" @selected(old('scoring_type', $exercise->rankStandard->scoring_type ?? 'estimated_1rm_absolute') === $value)>{{ $label }}</option>@endforeach
    </select>
  </label>
  <label><span class="ad-label">Olympian target</span><input name="olympian_target" type="number" min=".01" max="99999" step=".01" value="{{ old('olympian_target', $exercise->rankStandard->olympian_target ?? '') }}" data-rank-target></label>
  <label><span class="ad-label">Target unit</span>
    <select name="unit" required data-rank-unit>@foreach(['kg'=>'Kilograms','ratio'=>'Bodyweight ratio','reps'=>'Repetitions','none'=>'None'] as $value => $label)<option value="{{ $value }}" @selected(old('unit', $exercise->rankStandard->unit ?? 'kg') === $value)>{{ $label }}</option>@endforeach</select>
  </label>
  <label class="ad-check ad-field-wide"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $exercise->rankStandard->is_active ?? true))><span><strong>Ranking active</strong><small>Users can earn exercise ranks using this standard.</small></span></label>
</div>
<p class="ad-privacy-note">Changing a target affects future scoring. Existing workout logs and user streaks are never edited here.</p>
<div class="ad-form-actions"><a class="ad-button ad-button--secondary" href="{{ route('admin.exercises.index') }}">Cancel</a><button class="ad-button" type="submit">{{ $editing ? 'Save exercise' : 'Create exercise' }}</button></div>
<script>
  (() => {
    const type = document.querySelector('[data-scoring-type]');
    const target = document.querySelector('[data-rank-target]');
    const unit = document.querySelector('[data-rank-unit]');
    if (!type || !target || !unit) return;
    const sync = () => {
      const disabled = type.value === 'disabled';
      target.disabled = disabled;
      target.required = !disabled;
      if (disabled) unit.value = 'none';
    };
    type.addEventListener('change', sync);
    sync();
  })();
</script>

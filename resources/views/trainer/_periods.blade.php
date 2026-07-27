<div class="ch-control">
  <label class="ch-label">Time Period</label>
  <div class="ch-seg">
    @foreach(['week' => 'Week', 'month' => 'Month', 'year' => 'Year', 'all' => 'All Time'] as $period => $label)
      <button type="button" class="ch-segbtn {{ $active === $period ? 'is-active' : '' }}" {{ $attribute }} data-period-value="{{ $period }}">{{ $label }}</button>
    @endforeach
  </div>
</div>

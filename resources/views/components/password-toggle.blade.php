@props(['target'])

<button
  class="input-icon-btn"
  type="button"
  aria-controls="{{ $target }}"
  aria-pressed="false"
  aria-label="Show password"
  data-password-toggle
>
  <svg viewBox="0 0 24 24" aria-hidden="true">
    <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path>
    <circle cx="12" cy="12" r="2.7"></circle>
  </svg>
</button>

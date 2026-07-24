@php($editing = isset($subscription))
<div class="ad-form-grid">
  <label class="ad-field-wide">
    <span class="ad-label">User</span>
    <select name="user_id" required>
      @foreach($users as $account)
        <option value="{{ $account->id }}" @selected((int) old('user_id', $subscription->user_id ?? $selectedUserId ?? 0) === $account->id)>
          {{ $account->full_name ?? $account->name }} — {{ $account->email }}
        </option>
      @endforeach
    </select>
  </label>
  <label><span class="ad-label">Plan</span>
    <select name="plan" required>
      @foreach($plans as $value => $label)<option value="{{ $value }}" @selected(old('plan', $subscription->plan ?? 'paid') === $value)>{{ $label }}</option>@endforeach
    </select>
  </label>
  <label><span class="ad-label">Status</span>
    <select name="status" required>
      @foreach($statuses as $value => $label)<option value="{{ $value }}" @selected(old('status', $subscription->status ?? 'active') === $value)>{{ $label }}</option>@endforeach
    </select>
  </label>
  <label><span class="ad-label">Amount paid</span><input name="amount_paid" type="number" min="0" max="99999999.99" step=".01" required value="{{ old('amount_paid', $subscription->amount_paid ?? '0.00') }}"></label>
  <label><span class="ad-label">Currency</span><select name="currency" required><option value="EUR">EUR</option></select></label>
  <label><span class="ad-label">Starts on</span><input name="starts_on" type="date" required value="{{ old('starts_on', isset($subscription) ? $subscription->starts_on?->format('Y-m-d') : now()->toDateString()) }}"></label>
  <label><span class="ad-label">Ends on</span><input name="ends_on" type="date" value="{{ old('ends_on', isset($subscription) ? $subscription->ends_on?->format('Y-m-d') : '') }}"></label>
  <label class="ad-field-wide"><span class="ad-label">Payment received at</span><input name="paid_at" type="datetime-local" value="{{ old('paid_at', isset($subscription) ? $subscription->paid_at?->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}"></label>
  <label class="ad-field-wide"><span class="ad-label">Owner notes</span><textarea name="notes" maxlength="2000" rows="4">{{ old('notes', $subscription->notes ?? '') }}</textarea></label>
</div>
<p class="ad-privacy-note">Revenue is calculated from recorded payments. Refunded subscriptions are excluded from revenue totals.</p>
<div class="ad-form-actions"><a class="ad-button ad-button--secondary" href="{{ route('admin.subscriptions.index') }}">Cancel</a><button class="ad-button" type="submit">{{ $editing ? 'Save subscription' : 'Create subscription' }}</button></div>

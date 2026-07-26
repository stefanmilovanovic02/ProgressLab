@php($editing = isset($user))
<div class="ad-form-grid">
  <label><span class="ad-label">Full name</span><input name="full_name" required maxlength="255" value="{{ old('full_name', $user->full_name ?? $user->name ?? '') }}"></label>
  <label><span class="ad-label">Username</span><input name="username" required maxlength="80" value="{{ old('username', $user->username ?? '') }}"></label>
  <label><span class="ad-label">Email</span><input name="email" type="email" required value="{{ old('email', $user->email ?? '') }}"></label>
  <label><span class="ad-label">Role</span>
    <select name="role" required>
      @foreach($roles as $roleOption)
        <option value="{{ $roleOption->value }}" @selected(old('role', isset($user) ? $user->role->value : 'user') === $roleOption->value)>{{ $roleOption->label() }}</option>
      @endforeach
    </select>
    @if(auth()->user()->isOwner())
      <small class="ad-field-help">Selecting Paid grants access only. It does not create a billable subscription or increase revenue counters.</small>
    @endif
  </label>
  <label><span class="ad-label">Gender</span>
    <select name="gender"><option value="">Not specified</option><option value="male" @selected(old('gender', $user->gender ?? '') === 'male')>Male</option><option value="female" @selected(old('gender', $user->gender ?? '') === 'female')>Female</option></select>
  </label>
  <label><span class="ad-label">Date of birth</span><input name="date_of_birth" type="date" value="{{ old('date_of_birth', $user->date_of_birth ?? '') }}"></label>
  <label class="ad-field-wide"><span class="ad-label">Location</span><input name="location" maxlength="120" value="{{ old('location', $user->location ?? '') }}"></label>
  <label><span class="ad-label">{{ $editing ? 'New password (optional)' : 'Password' }}</span><input name="password" type="password" {{ $editing ? '' : 'required' }} minlength="8" autocomplete="new-password"></label>
  <label><span class="ad-label">Confirm password</span><input name="password_confirmation" type="password" {{ $editing ? '' : 'required' }} minlength="8" autocomplete="new-password"></label>
</div>
<p class="ad-privacy-note">Streaks, tracking history, and private progress photos cannot be edited from this form.</p>
<div class="ad-form-actions"><a class="ad-button ad-button--secondary" href="{{ route('admin.users.index') }}">Cancel</a><button class="ad-button" type="submit">{{ $editing ? 'Save changes' : 'Create user' }}</button></div>

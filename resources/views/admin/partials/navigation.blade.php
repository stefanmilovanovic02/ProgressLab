<nav class="ad-tabs" aria-label="Admin sections">
  <a class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}">Overview</a>
  <a class="{{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}" href="{{ route('admin.users.index') }}">Users</a>
  <a class="{{ request()->routeIs('admin.exercises.*') ? 'is-active' : '' }}" href="{{ route('admin.exercises.index') }}">Exercises</a>
  @if(auth()->user()->isOwner())
    <a class="{{ request()->routeIs('admin.subscriptions.*') ? 'is-active' : '' }}" href="{{ route('admin.subscriptions.index') }}">Subscriptions</a>
  @endif
</nav>

@if(session('status'))
  <div class="ad-alert ad-alert--success">{{ session('status') }}</div>
@endif

@if($errors->any())
  <div class="ad-alert ad-alert--error">
    <strong>Please check the following:</strong>
    <ul>
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<!doctype html>
<html lang="en">
<head><x-seo title="Edit User" description="Edit a ProgressLab user." robots="noindex, nofollow, noarchive" /><link rel="stylesheet" href="{{ asset('css/auth.css') }}"><link rel="stylesheet" href="{{ asset('css/admin.css') }}"></head>
<body class="auth-body"><x-navbar /><main class="pl-container ad-wrap">
  <header class="ad-head"><div><span class="ad-eyebrow">Users</span><h1>Edit {{ $user->full_name ?? $user->name }}</h1><p>Update account details without altering tracking records.</p></div></header>
  @include('admin.partials.navigation')
  <section class="ad-card ad-form-card"><form method="POST" action="{{ route('admin.users.update', $user) }}">@csrf @method('PUT') @include('admin.users._form')</form></section>
</main><x-footer /></body></html>

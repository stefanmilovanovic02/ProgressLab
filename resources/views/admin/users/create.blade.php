<!doctype html>
<html lang="en">
<head><x-seo title="Create User" description="Create a ProgressLab user." robots="noindex, nofollow, noarchive" /><link rel="stylesheet" href="{{ asset('css/auth.css') }}"><link rel="stylesheet" href="{{ asset('css/admin.css') }}"></head>
<body class="auth-body"><x-navbar /><main class="pl-container ad-wrap">
  <header class="ad-head"><div><span class="ad-eyebrow">Users</span><h1>Create user</h1><p>Create a basic account. Fitness profile information can be completed by the user.</p></div></header>
  @include('admin.partials.navigation')
  <section class="ad-card ad-form-card"><form method="POST" action="{{ route('admin.users.store') }}">@csrf @include('admin.users._form')</form></section>
</main><x-footer /></body></html>

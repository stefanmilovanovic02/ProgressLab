<!doctype html>
<html lang="en">
<head><x-seo title="Create Subscription" description="Create a ProgressLab subscription record." robots="noindex, nofollow, noarchive" /><link rel="stylesheet" href="{{ asset('css/auth.css') }}"><link rel="stylesheet" href="{{ asset('css/admin.css') }}"></head>
<body class="auth-body"><x-navbar /><main class="pl-container ad-wrap">
  <header class="ad-head"><div><span class="ad-eyebrow">Owner only</span><h1>Create subscription</h1><p>Record a plan and its payment information.</p></div></header>
  @include('admin.partials.navigation')
  <section class="ad-card ad-form-card"><form method="POST" action="{{ route('admin.subscriptions.store') }}">@csrf @include('admin.subscriptions._form')</form></section>
</main><x-footer /></body></html>

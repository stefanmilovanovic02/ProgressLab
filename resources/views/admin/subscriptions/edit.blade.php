<!doctype html>
<html lang="en">
<head><x-seo title="Edit Subscription" description="Edit a ProgressLab subscription record." robots="noindex, nofollow, noarchive" /><link rel="stylesheet" href="{{ asset('css/auth.css') }}"><link rel="stylesheet" href="{{ asset('css/admin.css') }}"></head>
<body class="auth-body"><x-navbar /><main class="pl-container ad-wrap">
  <header class="ad-head"><div><span class="ad-eyebrow">Owner only</span><h1>Edit subscription</h1><p>Update the plan, dates, status, or payment record.</p></div></header>
  @include('admin.partials.navigation')
  <section class="ad-card ad-form-card">
    <form method="POST" action="{{ route('admin.subscriptions.update', $subscription) }}">@csrf @method('PUT') @include('admin.subscriptions._form')</form>
    <div class="ad-divider"></div>
    <div class="ad-danger-zone"><div><h2>Remove subscription</h2><p>Deletes this billing record. It does not delete the user account.</p></div><form method="POST" action="{{ route('admin.subscriptions.destroy', $subscription) }}" onsubmit="return confirm('Remove this subscription record?')">@csrf @method('DELETE')<button class="ad-button ad-button--danger" type="submit">Remove</button></form></div>
  </section>
</main><x-footer /></body></html>

<!doctype html>
<html lang="en">
<head>
  <x-seo
    :title="trim($__env->yieldContent('title', 'ProgressLab'))"
    :description="trim($__env->yieldContent('description', 'Track workouts, nutrition, streaks, achievements, and fitness progress with ProgressLab.'))"
    robots="noindex, nofollow, noarchive"
  />
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body class="auth-body">

  <x-navbar />
  <main>
    @yield('content')




    
  </main>
</body>

</html>

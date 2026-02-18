<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>@yield('title', 'ProgressLab')</title>
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body class="auth-body">

  <x-navbar />
  <main>
    @yield('content')




    
  </main>
</body>

</html>

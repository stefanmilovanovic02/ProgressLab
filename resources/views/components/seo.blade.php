@props([
    'title' => 'Track Your Fitness Progress',
    'description' => 'Track workouts, nutrition, streaks, achievements, and fitness progress with ProgressLab.',
    'robots' => 'index, follow',
    'canonical' => null,
])

@php
    $pageTitle = $title === 'ProgressLab' ? $title : $title . ' • ProgressLab';
    $canonicalUrl = $canonical ?: url()->current();
    $socialImage = asset('images/branding/progresslab-og.png');
    $logo = asset('images/branding/progresslab-logo.png') . '?v=2';
    $favicon = asset('images/branding/progresslab-favicon.png');
    $touchIcon = asset('images/branding/progresslab-touch-icon.png');
@endphp

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ $description }}">
<meta name="robots" content="{{ $robots }}">
<meta name="theme-color" content="#071225">
<link rel="canonical" href="{{ $canonicalUrl }}">
<link rel="icon" type="image/png" sizes="256x256" href="{{ $favicon }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ $touchIcon }}">

<meta property="og:site_name" content="ProgressLab">
<meta property="og:type" content="website">
<meta property="og:locale" content="en_US">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:image" content="{{ $socialImage }}">
<meta property="og:image:secure_url" content="{{ $socialImage }}">
<meta property="og:image:width" content="1731">
<meta property="og:image:height" content="909">
<meta property="og:image:type" content="image/png">
<meta property="og:image:alt" content="ProgressLab — Track your progress, measure, achieve, and grow">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $pageTitle }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $socialImage }}">
<meta name="twitter:image:alt" content="ProgressLab — Track your progress, measure, achieve, and grow">

@if (str_starts_with($robots, 'index'))
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebApplication',
    'name' => 'ProgressLab',
    'url' => config('app.url'),
    'applicationCategory' => 'HealthApplication',
    'operatingSystem' => 'Web',
    'description' => $description,
    'logo' => $logo,
    'image' => $socialImage,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endif

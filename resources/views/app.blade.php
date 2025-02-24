<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>Allfliptix</title>
    <meta name="description"
        content="Welcome to allfliptix. From concerts and sports events to theater performances and festivals, we provide a diverse range of ticketing options to suit every entertainment enthusiast.">
    <meta name="keywords"
        content="Velzon, Inertia.js, Vue.js, Laravel, admin template, dashboard template, web application">
    <meta name="author" content="Project Web by Ghale">

    <!-- Social Media Meta Tags -->
    <meta property="og:title" content="Allfliptix">
    <meta property="og:description"
        content="Welcome to allfliptix. From concerts and sports events to theater performances and festivals, we provide a diverse range of ticketing options to suit every entertainment enthusiast.">
    <meta property="og:image" content="URL to the template's logo or featured image">
    <meta property="og:url" content="URL to the template's webpage">
    <meta name="twitter:card" content="summary_large_image">

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ URL::asset('image/favicon.ico') }}">

    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body>
    @inertia
</body>

</html>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">

        <title inertia>{{ config('app.name', 'نرخ‌نامه قیمت') }}</title>

        <!-- Primary Meta Tags -->
        <meta name="title" content="{{ config('app.name', 'نرخ‌نامه قیمت') }}">
        <meta name="description" content="نرخ‌نامه قیمت محصولات - اطلاعات بروز شده قیمت میوه و سبزیجات در بازارهای مختلف">
        <meta name="keywords" content="قیمت میوه, قیمت سبزیجات, نرخ‌نامه, بازار، خیار، گوجه‌فرنگی، فلفل، پیاز">
        
        <!-- Canonical URL -->
        <link rel="canonical" href="{{ request()->url() }}">

        <!-- Favicon -->
        <link rel="icon" type="image/x-icon" href="/favicon.ico">
        <link rel="apple-touch-icon" href="/favicon.ico">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link rel="dns-prefetch" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Site Verification (add your verification codes here if needed) -->
        <!-- <meta name="google-site-verification" content="your-code-here" />
             <meta name="msvalidate.01" content="your-code-here" /> -->

        <!-- Theme Color -->
        <meta name="theme-color" content="#1f2937">

        <!-- Open Graph Meta Tags (default values, can be overridden) -->
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ config('app.name', 'نرخ‌نامه قیمت') }}">
        <meta property="og:description" content="نرخ‌نامه قیمت محصولات - اطلاعات بروز شده قیمت میوه و سبزیجات">
        <meta property="og:image" content="{{ asset('og-image.jpg') }}">
        <meta property="og:url" content="{{ request()->url() }}">
        <meta property="og:site_name" content="{{ config('app.name', 'نرخ‌نامه قیمت') }}">
        <meta property="og:locale" content="fa_IR">

        <!-- Twitter Card Meta Tags -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ config('app.name', 'نرخ‌نامه قیمت') }}">
        <meta name="twitter:description" content="نرخ‌نامه قیمت محصولات">
        <meta name="twitter:image" content="{{ asset('og-image.jpg') }}">

        <!-- Language Declaration -->
        <meta name="language" content="Persian">
        <meta name="revisit-after" content="7 days">
        <meta name="author" content="نرخ‌نامه قیمت">

        <!-- Robots Meta -->
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">

        <!-- Additional SEO -->
        <meta name="format-detection" content="telephone=no">
        <meta name="format-detection" content="email=no">

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', 'resources/css/app.css'])
        @inertiaHead

        <!-- Structured Data (Organization) -->
        <script type="application/ld+json">
{!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => config('app.name', 'نرخ‌نامه قیمت'),
            'description' => 'نرخ‌نامه قیمت محصولات - اطلاعات بروز شده قیمت میوه و سبزیجات در بازارهای مختلف',
            'url' => config('app.url'),
            'logo' => asset('images/my-logo2.png'),
            'sameAs' => [
                'https://www.instagram.com/your-account',
                'https://www.telegram.me/your-account'
            ],
            'address' => [
                '@type' => 'PostalAddress',
                'addressCountry' => 'IR',
                'addressLocality' => 'ایران'
            ]
        ]) !!}
        </script>

        <!-- WebSite Schema for Search Box -->
        <script type="application/ld+json">
{!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'url' => config('app.url'),
            'name' => config('app.name', 'نرخ‌نامه قیمت'),
            'description' => 'نرخ‌نامه قیمت محصولات'
        ]) !!}
        </script>
    </head>
    <body class="font-sans antialiased text-right" lang="fa">
        @inertia
    </body>
</html>

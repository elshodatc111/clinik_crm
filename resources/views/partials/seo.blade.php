@php
    $seoDecode = fn ($v) => trim(html_entity_decode(strip_tags((string) $v), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $seoTitle = $seoDecode($__env->yieldContent('title')) ?: $brand['name'];
    $seoDesc = $seoDecode($__env->yieldContent('description')) ?: __("Xususiy klinikalar uchun to'liq avtomatlashtirilgan CRM tizimi");
    $seoUrl = rtrim(url()->current(), '/') ?: url('/');
    if ($seoUrl === rtrim(url('/'), '/')) {
        $seoUrl = url('/');
    }
    $seoImg = $brand['logo'] ?: url('/icons/icon-512.png');
    $seoLoc = app()->getLocale() === 'ru' ? 'ru_RU' : 'uz_UZ';
    $org = array_filter([
        '@context' => 'https://schema.org', '@type' => 'Organization', 'name' => $brand['name'], 'url' => url('/'),
        'logo' => url('/icons/icon-512.png'), 'email' => $brand['email'] ?? null, 'telephone' => $brand['phone'] ?? null,
    ]);
@endphp
<meta name="robots" content="index,follow,max-image-preview:large">
<link rel="canonical" href="{{ $seoUrl }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $brand['name'] }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ \Illuminate\Support\Str::limit($seoDesc, 200) }}">
<meta property="og:url" content="{{ $seoUrl }}">
<meta property="og:image" content="{{ $seoImg }}">
<meta property="og:locale" content="{{ $seoLoc }}">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ \Illuminate\Support\Str::limit($seoDesc, 200) }}">
<script type="application/ld+json">{!! json_encode($org, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@stack('seo')

@php
    $uri = request()->path();
    $title = 'Business Owner';
    $backUrl = url('/business');

    if (str_contains($uri, 'customers')) {
        $title = 'Customer';
    } elseif (str_contains($uri, 'products')) {
        $title = 'Products';
    } elseif (str_contains($uri, 'incoming-orders')) {
        $title = 'Transaction';
    } elseif (str_contains($uri, 'business-sales-report')) {
        $title = 'Reports';
    } elseif (str_contains($uri, 'profile')) {
        $title = 'Profile';
    }
@endphp

<!-- Mobile Top Header (Shown on Mobile < 992px in Business Panel) -->
<x-business.mobile-header :title="$title" :backUrl="$backUrl" />

<!-- Fullscreen Mobile Navigation Menu -->
<x-business.fullscreen-menu />

@props(['key', 'default'])
@php
    /** @var \App\Services\Landing\LandingContent $content */
    $content = app(\App\Services\Landing\LandingContent::class);
@endphp
<img src="{{ $content->imageUrl($key, $default) }}" {{ $attributes->merge($content->attrs($key)) }}>

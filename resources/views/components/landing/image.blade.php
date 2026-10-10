@props(['key' => null, 'default'])
@php
    /** @var \App\Services\Landing\LandingContent $content */
    $content = app(\App\Services\Landing\LandingContent::class);
@endphp
@php
    $extra = $key !== null && $content->editing() ? ['data-lf-stock' => asset($default)] : [];
@endphp
<img src="{{ $key !== null ? $content->imageUrl($key, $default) : asset($default) }}" {{ $attributes->merge($key !== null ? $content->attrs($key) + $extra : []) }}>

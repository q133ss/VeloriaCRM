@props(['key', 'tag' => 'span', 'index' => null, 'default' => null, 'defaults' => [], 'titleFallback' => true])
@php
    /** @var \App\Services\Landing\LandingContent $content */
    $content = app(\App\Services\Landing\LandingContent::class);
    $kind = \App\Services\Landing\LandingContent::field($key)['kind'] ?? 'text';
    $value = $index !== null ? ($content->items($key, (array) $defaults)[$index] ?? '') : $content->text($key, $default, (bool) $titleFallback);
@endphp
<{{ $tag }} {{ $attributes->merge($content->attrs($key, $index)) }}@if($kind === 'textarea') style="white-space: pre-line"@endif>{{ $value }}</{{ $tag }}>

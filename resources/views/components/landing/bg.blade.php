@props(['key', 'default', 'tag' => 'div'])
@php
    /** @var \App\Services\Landing\LandingContent $content */
    $content = app(\App\Services\Landing\LandingContent::class);
    $url = $content->imageUrl($key, $default);
@endphp
<{{ $tag }} {{ $attributes->merge($content->attrs($key)) }} style="background-image: url('{{ $url }}');">{{ $slot }}</{{ $tag }}>

@props(['key' => null, 'default', 'tag' => 'div', 'var' => null])
@php
    /** @var \App\Services\Landing\LandingContent $content */
    $content = app(\App\Services\Landing\LandingContent::class);
    $url = $key !== null ? $content->imageUrl($key, $default) : asset($default);
    $extra = $key !== null && $content->editing() ? ['data-lf-stock' => asset($default)] : [];
    // `var`: the layout paints the photo itself (a ::after layer) and reads it from this custom property.
    if ($var !== null && $key !== null && $content->editing()) {
        $extra['data-lf-bgvar'] = '--' . $var;
    }
    $paint = $var !== null ? '--' . $var . ": url('" . $url . "')" : "background-image: url('" . $url . "')";
@endphp
<{{ $tag }} {{ $attributes->except('style')->merge($key !== null ? $content->attrs($key) + $extra : []) }} style="{{ $paint }};{{ $attributes->get('style') }}">{{ $slot }}</{{ $tag }}>

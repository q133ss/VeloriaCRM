@props(['class' => ''])
<div {{ $attributes->merge(['class' => 'meta-notice ' . $class, 'role' => 'note']) }}
     style="margin:.75rem 0;padding:.5rem .75rem;border:1px solid currentColor;border-radius:8px;font-size:.8125rem;line-height:1.4;opacity:.85;text-align:left;font-weight:400;">
    {{ __('notices.meta_extremist') }}
</div>

{{--
    Booking for full-page and classic templates: free windows from the master's schedule,
    booking on one of them, a plain request when no time is picked, a confirmation
    window and the phone mask. A template only supplies markup with the right ids;
    the logic lives in public/landing-booking/booking.js.

    @include('landings.partials.request-script', ['ids' => [
        'form' => 'my-form',          // <form>; fields named client_name, client_phone, service_id, message
        'button' => 'my-submit',      // submit button id
        'message' => 'my-message',    // element that receives errors
        'service' => 'my-service',    // optional <select name="service_id"> id
        'picker' => 'my-picker',      // empty element where the date and time picker is drawn
    ], 'accent' => '#7f5af0', 'accentText' => '#fff'])

    Give the phone input the attribute data-phone-mask.
    Elements with [data-pick-service="ID"] preselect that service in the select.
--}}
@php
    $ids = array_merge(['form' => 'request-form', 'button' => 'request-submit', 'message' => 'request-message', 'service' => null, 'picker' => null], $ids ?? []);
    $bookingConfig = [
        'ids' => $ids,
        'urls' => [
            'lead' => route('landings.request', ['slug' => $landing->slug]),
            'availability' => route('landings.availability', ['slug' => $landing->slug]),
            'prepayment' => route('landings.prepayment', ['slug' => $landing->slug]),
            'book' => route('landings.book', ['slug' => $landing->slug]),
        ],
        'i18n' => __('landings.booking.ui'),
        'failed' => __('landings.public.request_failed'),
        'editing' => ! empty($isEdit) || ! empty($isDemo),
    ];
@endphp
<link rel="stylesheet" href="{{ asset('landing-booking/booking.css') }}?v={{ filemtime(public_path('landing-booking/booking.css')) }}">
<style>:root { --lb-accent: {{ $accent ?? '#7f5af0' }}; --lb-accent-text: {{ $accentText ?? '#fff' }}; }</style>
<script>window.LandingBookingConfig = @json($bookingConfig);</script>
@include('components.phone-mask-script')
<script>
    // Lets a "Book" button next to a service preselect it in the form.
    document.addEventListener('DOMContentLoaded', function () {
        var select = document.getElementById(@json($ids['service']));
        document.querySelectorAll('[data-pick-service]').forEach(function (link) {
            link.addEventListener('click', function () {
                var id = link.getAttribute('data-pick-service');
                if (id && select) {
                    select.value = id;
                    select.dispatchEvent(new Event('change'));
                }
            });
        });
    });
</script>
<script src="{{ asset('landing-booking/booking.js') }}?v={{ filemtime(public_path('landing-booking/booking.js')) }}" defer></script>

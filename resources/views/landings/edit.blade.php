@extends('layouts.app')

@section('title', __('landings.edit.title'))

@section('content')
    @include('landings.partials.form')
@endsection

@section('scripts')
    <script>
        window.LANDING_FORM_CONFIG = {
            mode: 'edit',
            landingId: {{ (int) ($landingId ?? 0) }},
            appUrl: '{{ rtrim(config('app.url'), '/') }}',
            translations: @json(__('landings.notifications')),
            typeLabels: @json(__('landings.types')),
            statusLabels: @json(__('landings.statuses')),
            defaultTemplate: {
                general: 'landings.templates.general',
                promotion: 'landings.templates.promotion',
                service: 'landings.templates.service'
            }
        };
    </script>
    @include('landings.partials.form-script')
@endsection

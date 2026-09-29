@extends('layouts.app')

@section('title', __('landings.create.title'))

@section('content')
    @include('landings.partials.wizard')
@endsection

@section('scripts')
    <script>
        window.LANDING_WIZARD_CONFIG = {
            appUrl: '{{ rtrim(config('app.url'), '/') }}',
            listUrl: '{{ route('landings.index') }}',
            servicesUrl: '{{ url('/services') }}',
            marketingUrl: '{{ url('/marketing') }}',
            layouts: @json(app(\App\Services\Landing\TemplateRegistry::class)->forWizard()),
            categories: @json(app(\App\Services\Landing\TemplateRegistry::class)->categoryChips()),
            purposes: @json(app(\App\Services\Landing\TemplateRegistry::class)->purposeChips())
        };
    </script>
    @include('components.phone-mask-script')
    @include('landings.partials.wizard-script')
@endsection

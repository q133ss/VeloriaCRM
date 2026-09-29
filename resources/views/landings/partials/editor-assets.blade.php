@if(!empty($isEdit))
    @php
        $lfConfig = [
            'contentUrl' => url('/api/v1/landings/' . $landing->id . '/content'),
            'imagesUrl' => url('/api/v1/landings/' . $landing->id . '/images'),
            'publicUrl' => url('/l/' . $landing->slug),
            'doneUrl' => route('landings.index'),
            'i18n' => __('landings.editor'),
        ];
    @endphp
    <link rel="stylesheet" href="{{ asset('landing-editor/editor.css') }}?v={{ filemtime(public_path('landing-editor/editor.css')) }}">
    <script>window.LF = @json($lfConfig);</script>
    <script src="{{ asset('landing-editor/editor.js') }}?v={{ filemtime(public_path('landing-editor/editor.js')) }}" defer></script>
@endif

@props([
    'id',
    'name',
    'label' => 'Дата и время',
    'value' => '',
    'required' => false,
    'placeholder' => 'Выберите дату и время',
    'helper' => 'Нажмите на поле, чтобы открыть календарь, или выберите быстрый слот ниже.',
    'timeSlots' => ['09:00', '11:00', '13:00', '15:00', '18:00'],
])

<div class="veloria-datetime-field" data-veloria-datetime-root data-time-slots="{{ implode(',', $timeSlots) }}">
    <label for="{{ $id }}_display" class="veloria-datetime-label">{{ $label }}</label>

    <input
        type="hidden"
        id="{{ $id }}"
        name="{{ $name }}"
        value="{{ $value }}"
        data-veloria-datetime-value
        @if($required) required @endif
    />

    <div class="veloria-datetime-display-wrap">
        <input
            type="text"
            id="{{ $id }}_display"
            class="form-control veloria-datetime-display"
            data-veloria-datetime-display
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            readonly
        />
        <button type="button" class="veloria-datetime-open" data-veloria-datetime-open aria-label="{{ $label }}">
            <i class="ri ri-calendar-schedule-line"></i>
        </button>
    </div>

    @if($helper)
        <div class="form-text veloria-datetime-help">{{ $helper }}</div>
    @endif

    <div class="veloria-datetime-shortcuts">
        <button type="button" class="btn veloria-datetime-chip veloria-datetime-chip--day" data-datetime-day="0">Сегодня</button>
        <button type="button" class="btn veloria-datetime-chip veloria-datetime-chip--day" data-datetime-day="1">Завтра</button>
        @foreach($timeSlots as $slot)
            <button type="button" class="btn veloria-datetime-chip" data-datetime-time="{{ $slot }}">{{ $slot }}</button>
        @endforeach
    </div>
</div>

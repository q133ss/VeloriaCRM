@extends('layouts.app')

@section('title', 'Карточка клиента')

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">{{ $client->name }}</h4>
            <p class="text-muted mb-0">Карточка клиента с детальной информацией и историей взаимодействий.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('clients.edit', $client) }}" class="btn btn-primary">
                <i class="ri ri-pencil-line me-1"></i>
                Редактировать
            </a>
            <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">
                <i class="ri ri-arrow-left-line me-1"></i>
                Назад к списку
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-4">Основная информация</h5>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="text-muted text-uppercase small mb-1">Контакты</div>
                            <p class="mb-1">
                                <i class="ri ri-phone-line me-1"></i>
                                {{ $client->phone ?? '—' }}
                            </p>
                            <p class="mb-0">
                                <i class="ri ri-mail-line me-1"></i>
                                {{ $client->email ?? '—' }}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted text-uppercase small mb-1">Профиль</div>
                            <p class="mb-1">День рождения: {{ $client->birthday?->translatedFormat('d F Y') ?? '—' }}</p>
                            <p class="mb-1">Последний визит: {{ $client->last_visit_at?->format('d.m.Y H:i') ?? '—' }}</p>
                            <p class="mb-0">Уровень лояльности: {{ $client->loyalty_level ?? 'Не задан' }}</p>
                        </div>
                        <div class="col-12">
                            <div class="text-muted text-uppercase small mb-1">Дополнительно</div>
                            @if (!empty($client->tags))
                                <p class="mb-1">Теги: {{ implode(', ', $client->tags) }}</p>
                            @endif
                            @if (!empty($client->preferences))
                                <p class="mb-1">Предпочтения: {{ implode(', ', $client->preferences) }}</p>
                            @endif
                            @if (!empty($client->allergies))
                                <p class="mb-1">Аллергии: {{ implode(', ', $client->allergies) }}</p>
                            @endif
                            <p class="mb-0">Заметки: {{ $client->notes ?? '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-4">Быстрые действия</h5>
                    <p class="text-muted small">
                        Отправьте автонапоминание или добавьте заметку, чтобы не упустить следующий визит клиента.
                    </p>
                    <button
                        type="button"
                        class="btn btn-outline-info w-100 mb-2"
                        data-bs-toggle="modal"
                        data-bs-target="#clientReminderModal{{ $client->id }}"
                    >
                        <i class="ri ri-notification-4-line me-1"></i>
                        Автонапоминание
                    </button>
                    <a href="{{ route('clients.edit', $client) }}" class="btn btn-outline-primary w-100">
                        <i class="ri ri-pencil-line me-1"></i>
                        Добавить заметку
                    </a>
                </div>
            </div>
        </div>
    </div>

    @php
        $channels = [];
        if ($client->phone) {
            $channels['sms'] = 'SMS';
            $channels['whatsapp'] = 'WhatsApp';
        }
        if ($client->email) {
            $channels['email'] = 'Email';
        }
        $settings = auth()->user()?->setting;
        $reminderMessage = $settings->reminder_message ?? '';
    @endphp

    <div class="modal fade" id="clientReminderModal{{ $client->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Автонапоминание для {{ $client->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form onsubmit="return false;">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="reminderMessage{{ $client->id }}" class="form-label">Текст напоминания</label>
                            <textarea id="reminderMessage{{ $client->id }}" class="form-control" rows="4">{{ $reminderMessage }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Канал связи</label>
                            @if (!empty($channels))
                                <div class="d-flex flex-column gap-2">
                                    @foreach ($channels as $key => $label)
                                        <div class="form-check">
                                            <input
                                                class="form-check-input"
                                                type="radio"
                                                name="channel{{ $client->id }}"
                                                id="channel{{ $client->id }}{{ $key }}"
                                                value="{{ $key }}"
                                                @checked($loop->first)
                                            />
                                            <label class="form-check-label" for="channel{{ $client->id }}{{ $key }}">
                                                {{ $label }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted mb-0">Добавьте контактные данные, чтобы выбрать канал связи.</p>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="button" class="btn btn-primary">Отправить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

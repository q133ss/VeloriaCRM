@extends('layouts.app')

@section('title', 'Клиенты')

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">Клиенты</h4>
            <p class="text-muted mb-0">Ведите базу клиентов, отслеживайте визиты и поддерживайте связь.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('clients.create') }}" class="btn btn-primary">
                <i class="ri ri-user-add-line me-1"></i>
                Добавить клиента
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex flex-column flex-md-row gap-2 align-items-md-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <h5 class="mb-0">Мои клиенты</h5>
                <span class="badge bg-label-secondary">{{ $clients->count() }}</span>
            </div>
            <form class="position-relative" method="GET" action="{{ route('clients.index') }}">
                <span class="position-absolute top-50 translate-middle-y ps-3 text-muted">
                    <i class="ri ri-search-line"></i>
                </span>
                <input
                    type="text"
                    class="form-control ps-5"
                    name="search"
                    placeholder="Поиск по имени или телефону"
                    value="{{ $search ?? '' }}"
                />
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Клиент</th>
                        <th>Контакты</th>
                        <th>День рождения</th>
                        <th>Последний визит</th>
                        <th>Лояльность</th>
                        <th class="text-end">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clients as $client)
                        @php
                            $channels = [];
                            if ($client->phone) {
                                $channels['sms'] = 'SMS';
                                $channels['whatsapp'] = 'WhatsApp';
                            }
                            if ($client->email) {
                                $channels['email'] = 'Email';
                            }
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $client->name }}</div>
                                @if (!empty($client->tags))
                                    <div class="small text-muted">{{ implode(', ', $client->tags) }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    @if ($client->phone)
                                        <span><i class="ri ri-phone-line me-1 text-muted"></i>{{ $client->phone }}</span>
                                    @endif
                                    @if ($client->email)
                                        <span><i class="ri ri-mail-line me-1 text-muted"></i>{{ $client->email }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                {{ $client->birthday?->translatedFormat('d F Y') ?? '—' }}
                            </td>
                            <td>
                                {{ $client->last_visit_at?->format('d.m.Y H:i') ?? '—' }}
                            </td>
                            <td>
                                <span class="badge bg-label-primary">{{ $client->loyalty_level ?? 'Не задан' }}</span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group" role="group">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#clientQuickViewModal{{ $client->id }}"
                                    >
                                        Быстрый просмотр
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-info"
                                        data-bs-toggle="modal"
                                        data-bs-target="#clientReminderModal{{ $client->id }}"
                                    >
                                        Автонапоминание
                                    </button>
                                    <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-outline-primary">
                                        Редактировать
                                    </a>
                                    <form
                                        action="{{ route('clients.destroy', $client) }}"
                                        method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Удалить клиента {{ $client->name }}?');"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Удалить</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                Пока нет клиентов. Добавьте первого, чтобы начать вести базу.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @foreach ($clients as $client)
        @php
            $channels = [];
            if ($client->phone) {
                $channels['sms'] = 'SMS';
                $channels['whatsapp'] = 'WhatsApp';
            }
            if ($client->email) {
                $channels['email'] = 'Email';
            }
            $reminderMessage = optional($settings)->reminder_message ?? '';
        @endphp
        <div class="modal fade" id="clientQuickViewModal{{ $client->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $client->name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase mb-2">Контакты</h6>
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
                                <h6 class="text-muted text-uppercase mb-2">Профиль</h6>
                                <p class="mb-1">День рождения: {{ $client->birthday?->translatedFormat('d F Y') ?? '—' }}</p>
                                <p class="mb-0">Лояльность: {{ $client->loyalty_level ?? 'Не задан' }}</p>
                            </div>
                            <div class="col-md-12">
                                <h6 class="text-muted text-uppercase mb-2">Предпочтения и заметки</h6>
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
                    <div class="modal-footer">
                        <a href="{{ route('clients.show', $client) }}" class="btn btn-outline-primary">Подробнее</a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                    </div>
                </div>
            </div>
        </div>

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
                                <textarea
                                    id="reminderMessage{{ $client->id }}"
                                    class="form-control"
                                    rows="4"
                                >{{ $reminderMessage }}</textarea>
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
                                    <p class="text-muted mb-0">
                                        Добавьте контактные данные, чтобы выбрать канал связи.
                                    </p>
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
    @endforeach
@endsection

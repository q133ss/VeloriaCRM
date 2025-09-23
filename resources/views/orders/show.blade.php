@extends('layouts.app')

@section('title', 'Детали записи')

@section('content')
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">Запись #{{ $order->id }}</h4>
            <p class="text-muted mb-0">{{ optional($order->scheduled_at)->format('d.m.Y H:i') ?? 'Дата не указана' }} · {{ $order->status_label }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('orders.edit', $order) }}" class="btn btn-outline-secondary">
                <i class="ri ri-pencil-line me-1"></i>
                Редактировать
            </a>
            <form method="POST" action="{{ route('orders.complete', $order) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="ri ri-checkbox-circle-line me-1"></i>
                    Завершить
                </button>
            </form>
            <button type="button" class="btn btn-warning text-white" data-bs-toggle="modal" data-bs-target="#rescheduleModal">
                <i class="ri ri-calendar-event-line me-1"></i>
                Перенести
            </button>
            <form method="POST" action="{{ route('orders.remind', $order) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-info text-white">
                    <i class="ri ri-mail-line me-1"></i>
                    Напомнить
                </button>
            </form>
            <form method="POST" action="{{ route('orders.start', $order) }}" class="d-inline" id="start-form">
                @csrf
                @php $canStartToday = optional($order->scheduled_at)->isToday(); @endphp
                <button
                    type="submit"
                    class="btn btn-primary"
                    id="start-button"
                    {{ $canStartToday ? '' : 'disabled' }}
                    data-scheduled="{{ optional($order->scheduled_at)->toIso8601String() }}"
                >
                    <i class="ri ri-play-circle-line me-1"></i>
                    Начать работу
                </button>
            </form>
            <button
                type="button"
                class="btn {{ $hasProAccess ? 'btn-outline-dark' : 'btn-outline-secondary' }}"
                data-bs-toggle="modal"
                data-bs-target="#analyticsModal"
                {{ $hasProAccess ? '' : 'data-pro-required="true"' }}
            >
                <i class="ri ri-bar-chart-2-line me-1"></i>
                Аналитика клиента
            </button>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('reminder_text'))
        <div class="alert alert-info alert-dismissible" role="alert">
            <strong>Отправьте клиенту:</strong>
            <div class="mt-2 small">{!! nl2br(e(session('reminder_text'))) !!}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @elseif(empty($reminderMessage))
        <div class="alert alert-warning alert-dismissible" role="alert">
            Напоминания будут доступнее, если заполнить шаблон в настройках.
            <a href="{{ route('settings') }}" class="alert-link">Перейти</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Основная информация</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="text-muted">Клиент</h6>
                            <p class="mb-1 fw-medium">{{ $order->client?->name ?? 'Без имени' }}</p>
                            <p class="text-muted mb-0">{{ $order->client?->phone ?? '—' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Мастер</h6>
                            <p class="mb-0">{{ $order->master?->name ?? 'Не назначен' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Услуги</h6>
                            @php $services = collect($order->services ?? []); @endphp
                            @if($services->isEmpty())
                                <p class="text-muted mb-0">Не добавлены</p>
                            @else
                                <ul class="list-unstyled mb-0">
                                    @foreach($services as $service)
                                        <li class="d-flex justify-content-between">
                                            <span>{{ $service['name'] ?? 'Услуга' }}</span>
                                            <span class="text-muted">{{ isset($service['price']) ? number_format($service['price'], 2, '.', ' ') . ' ₽' : '' }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Итоговая сумма</h6>
                            <p class="fw-medium mb-1">{{ $order->total_price !== null ? number_format($order->total_price, 2, '.', ' ') . ' ₽' : 'Не указано' }}</p>
                            <span class="badge {{ $order->status_class }}">{{ $order->status_label }}</span>
                        </div>
                        <div class="col-12">
                            <h6 class="text-muted">Заметка мастеру</h6>
                            <p class="mb-0">{{ $order->note ?? '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Время и длительность</h5>
                </div>
                <div class="card-body row g-4">
                    <div class="col-md-4">
                        <h6 class="text-muted">Запланировано</h6>
                        <p class="mb-0">{{ optional($order->scheduled_at)->format('d.m.Y H:i') ?? '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-muted">Фактический старт</h6>
                        <p class="mb-0">{{ optional($order->actual_started_at)->format('d.m.Y H:i') ?? '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-muted">Завершение</h6>
                        <p class="mb-0">{{ optional($order->actual_finished_at)->format('d.m.Y H:i') ?? '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-muted">Плановая длительность</h6>
                        <p class="mb-0">{{ $order->duration_forecast ? $order->duration_forecast . ' мин' : '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-muted">Фактическая длительность</h6>
                        <p class="mb-0">{{ $order->duration ? $order->duration . ' мин' : '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-muted">Переносы</h6>
                        <p class="mb-0">{{ $order->reschedule_count ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">История</h5>
                </div>
                <div class="card-body">
                    <ul class="timeline timeline-center">
                        <li class="timeline-item">
                            <span class="timeline-indicator bg-primary"><i class="ri ri-file-list-2-line"></i></span>
                            <div class="timeline-event">
                                <div class="timeline-header">
                                    <h6 class="mb-0">Запись создана</h6>
                                    <small class="text-muted">{{ optional($order->created_at)->format('d.m.Y H:i') ?? '—' }}</small>
                                </div>
                                <p class="mb-0">Стартовый статус: {{ $order->status_label }}</p>
                            </div>
                        </li>
                        @if($order->confirmed_at)
                            <li class="timeline-item">
                                <span class="timeline-indicator bg-success"><i class="ri ri-check-line"></i></span>
                                <div class="timeline-event">
                                    <div class="timeline-header">
                                        <h6 class="mb-0">Подтверждено</h6>
                                        <small class="text-muted">{{ $order->confirmed_at->format('d.m.Y H:i') }}</small>
                                    </div>
                                    <p class="mb-0">Клиент подтвердил запись.</p>
                                </div>
                            </li>
                        @endif
                        @if($order->reminded_at)
                            <li class="timeline-item">
                                <span class="timeline-indicator bg-info"><i class="ri ri-mail-open-line"></i></span>
                                <div class="timeline-event">
                                    <div class="timeline-header">
                                        <h6 class="mb-0">Напоминание отправлено</h6>
                                        <small class="text-muted">{{ $order->reminded_at->format('d.m.Y H:i') }}</small>
                                    </div>
                                    <p class="mb-0">Клиент получил уведомление о визите.</p>
                                </div>
                            </li>
                        @endif
                        @if($order->reschedule_count > 0)
                            <li class="timeline-item">
                                <span class="timeline-indicator bg-warning"><i class="ri ri-time-line"></i></span>
                                <div class="timeline-event">
                                    <div class="timeline-header">
                                        <h6 class="mb-0">Перенос</h6>
                                        <small class="text-muted">{{ optional($order->updated_at)->format('d.m.Y H:i') }}</small>
                                    </div>
                                    <p class="mb-0">Переносов всего: {{ $order->reschedule_count }}</p>
                                </div>
                            </li>
                        @endif
                        @if($order->cancelled_at)
                            <li class="timeline-item">
                                <span class="timeline-indicator bg-danger"><i class="ri ri-close-circle-line"></i></span>
                                <div class="timeline-event">
                                    <div class="timeline-header">
                                        <h6 class="mb-0">Отмена</h6>
                                        <small class="text-muted">{{ $order->cancelled_at->format('d.m.Y H:i') }}</small>
                                    </div>
                                    <p class="mb-0">{{ $order->cancellation_reason ?? 'Причина не указана.' }}</p>
                                </div>
                            </li>
                        @endif
                        <li class="timeline-item">
                            <span class="timeline-indicator bg-secondary"><i class="ri ri-chat-1-line"></i></span>
                            <div class="timeline-event">
                                <div class="timeline-header">
                                    <h6 class="mb-0">Коммуникации</h6>
                                    <small class="text-muted">Заглушка</small>
                                </div>
                                <p class="mb-0">Скоро здесь появится история сообщений и звонков.</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Рекомендации ИИ</h5>
                    <span class="badge bg-label-info">Бета</span>
                </div>
                <div class="card-body">
                    @if($hasProAccess)
                        @php $recommended = collect($order->recommended_services ?? []); @endphp
                        @if($recommended->isNotEmpty())
                            <ul class="list-unstyled mb-0">
                                @foreach($recommended as $item)
                                    <li class="mb-3">
                                        <div class="fw-medium">{{ $item['name'] ?? 'Услуга' }}</div>
                                        <small class="text-muted">{{ $item['description'] ?? 'Индивидуальная рекомендация (заглушка).' }}</small>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted mb-0">Скоро здесь появятся персональные предложения для клиента.</p>
                        @endif
                    @else
                        <p class="mb-2">Подписка PRO откроет персональные рекомендации, прогноз длительности и предложения доп. услуг.</p>
                        <a href="#" class="btn btn-sm btn-primary disabled" aria-disabled="true">Обновить тариф</a>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Быстрые действия</h5>
                </div>
                <div class="card-body d-grid gap-2">
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#rescheduleModal">
                        <i class="ri ri-calendar-line me-1"></i> Перенести запись
                    </button>
                    <form method="POST" action="{{ route('orders.remind', $order) }}">
                        @csrf
                        <button class="btn btn-outline-info text-info">
                            <i class="ri ri-notification-3-line me-1"></i> Отправить напоминание
                        </button>
                    </form>
                    <form method="POST" action="{{ route('orders.cancel', $order) }}" onsubmit="return confirm('Отменить запись?');">
                        @csrf
                        <button class="btn btn-outline-danger">
                            <i class="ri ri-close-line me-1"></i> Отменить запись
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Reschedule -->
    <div class="modal fade" id="rescheduleModal" tabindex="-1" aria-labelledby="rescheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rescheduleModalLabel">Перенести запись</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('orders.reschedule', $order) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="form-floating form-floating-outline">
                            <input type="datetime-local" class="form-control" id="new_scheduled_at" name="scheduled_at" value="{{ optional($order->scheduled_at)->format('Y-m-d\\TH:i') }}" required />
                            <label for="new_scheduled_at">Новая дата и время</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Analytics -->
    <div class="modal fade" id="analyticsModal" tabindex="-1" aria-labelledby="analyticsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="analyticsModalLabel">Аналитика клиента</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($hasProAccess)
                        <p class="mb-3">Здесь появятся ключевые метрики клиента: LTV, средний чек, любимые услуги и прогноз следующего визита. (Заглушка)</p>
                        <div class="row g-4">
                            <div class="col-md-4">
                                <div class="border rounded p-3">
                                    <small class="text-muted">Средний чек</small>
                                    <div class="h4 mb-0">{{ $order->total_price ? number_format($order->total_price, 2, '.', ' ') . ' ₽' : '—' }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3">
                                    <small class="text-muted">Последний визит</small>
                                    <div class="h4 mb-0">{{ optional($order->scheduled_at)->format('d.m.Y') ?? '—' }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3">
                                    <small class="text-muted">Вероятность возврата</small>
                                    <div class="h4 mb-0">82% <span class="badge bg-label-success">ИИ</span></div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="ri ri-lock-2-line display-6 mb-3 text-muted"></i>
                            <p class="mb-2">Аналитика доступна только на тарифах PRO и Elite.</p>
                            <p class="text-muted">Подключите продвинутую аналитику, чтобы видеть прогнозы и историю поведения клиентов.</p>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Закрыть</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const startButton = document.getElementById('start-button');
            const startForm = document.getElementById('start-form');

            if (startButton && startForm) {
                startForm.addEventListener('submit', function (event) {
                    if (startButton.disabled) {
                        event.preventDefault();
                        window.alert('Начать работу можно только в день записи.');
                        return false;
                    }

                    const scheduledAt = startButton.dataset.scheduled;
                    if (!scheduledAt) {
                        return true;
                    }

                    const scheduledDate = new Date(scheduledAt);
                    const now = new Date();
                    const diffMinutes = (scheduledDate.getTime() - now.getTime()) / 60000;

                    if (diffMinutes > 60) {
                        const confirmStart = window.confirm('До записи ещё более часа. Вы уверены, что хотите начать работу уже сейчас?');
                        if (!confirmStart) {
                            event.preventDefault();
                            return false;
                        }
                    }

                    return true;
                });
            }
        });
    </script>
@endsection

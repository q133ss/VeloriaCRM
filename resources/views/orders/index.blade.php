@extends('layouts.app')

@section('title', 'Записи')

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">Записи</h4>
            <p class="text-muted mb-0">Управляйте расписанием, подтверждайте визиты и напоминания клиентам.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#quickCreateModal">
                <i class="ri ri-flashlight-line me-1"></i>
                Быстрое создание
            </button>
            <a href="{{ route('orders.create') }}" class="btn btn-primary">
                <i class="ri ri-add-line me-1"></i>
                Новая запись
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('reminder_text'))
        <div class="alert alert-info alert-dismissible" role="alert">
            <strong>Текст автонапоминания:</strong>
            <div class="mt-2 small">{!! nl2br(e(session('reminder_text'))) !!}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @elseif(empty($reminderMessage))
        <div class="alert alert-warning alert-dismissible" role="alert">
            Добавьте текст автонапоминания в настройках, чтобы быстро отправлять сообщения клиентам.
            <a href="{{ route('settings') }}" class="alert-link">Перейти в настройки</a>.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form method="GET" action="{{ route('orders.index') }}" class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="period" class="form-label">Период</label>
                    <select class="form-select" id="period" name="period">
                        @foreach($periodOptions as $value => $label)
                            <option value="{{ $value }}" {{ ($filters['period'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Статус</label>
                    <select class="form-select" id="status" name="status">
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="search" class="form-label">Быстрый поиск</label>
                    <input
                        type="text"
                        class="form-control"
                        id="search"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Имя или телефон клиента"
                    />
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">Применить</button>
                    <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary flex-fill">Сбросить</a>
                </div>
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('orders.bulk-action') }}" class="card" id="orders-bulk-form">
        @csrf
        <div class="card-header d-flex flex-column flex-md-row gap-2 gap-md-3 align-items-md-center justify-content-md-between">
            <div class="d-flex align-items-center gap-2">
                <h5 class="mb-0">Список записей</h5>
                <span class="badge bg-label-secondary">{{ $orders->total() }}</span>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" name="action" value="confirm" class="btn btn-success btn-sm">
                    <i class="ri ri-check-double-line me-1"></i>
                    Подтвердить выбранные
                </button>
                <button
                    type="submit"
                    name="action"
                    value="remind"
                    class="btn btn-info btn-sm text-white"
                    {{ empty($reminderMessage) ? 'disabled' : '' }}
                    @if(empty($reminderMessage)) title="Добавьте текст автонапоминания в настройках" @endif
                >
                    <i class="ri ri-mail-line me-1"></i>
                    Напомнить о записи
                </button>
                <button type="submit" name="action" value="cancel" class="btn btn-outline-danger btn-sm" id="bulk-cancel-btn">
                    <i class="ri ri-close-circle-line me-1"></i>
                    Отменить
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" class="form-check-input" id="select-all" />
                        </th>
                        <th>Дата / Время</th>
                        <th>Клиент 📞</th>
                        <th>Услуги</th>
                        <th>Статус</th>
                        <th class="text-end">Сумма</th>
                        <th class="text-end">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>
                                <input type="checkbox" class="form-check-input order-checkbox" name="orders[]" value="{{ $order->id }}" />
                            </td>
                            <td>
                                <div class="fw-medium">{{ optional($order->scheduled_at)->format('d.m.Y H:i') ?? '—' }}</div>
                                <small class="text-muted">{{ $order->master?->name }}</small>
                            </td>
                            <td>
                                <div class="fw-medium">{{ $order->client?->name ?? 'Без имени' }}</div>
                                <small class="text-muted">{{ $order->client?->phone ?? '—' }}</small>
                            </td>
                            <td>
                                @php
                                    $serviceNames = collect($order->services ?? [])->pluck('name')->filter()->values();
                                @endphp
                                @if($serviceNames->isNotEmpty())
                                    <div class="d-flex flex-column">
                                        @foreach($serviceNames->take(2) as $serviceName)
                                            <span>{{ $serviceName }}</span>
                                        @endforeach
                                        @if($serviceNames->count() > 2)
                                            <span class="text-muted small">+ ещё {{ $serviceNames->count() - 2 }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">Не выбраны</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $order->status_class }}">{{ $order->status_label }}</span>
                            </td>
                            <td class="text-end">
                                {{ $order->total_price !== null ? number_format($order->total_price, 2, '.', ' ') . ' ₽' : '—' }}
                            </td>
                            <td class="text-end">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-icon btn-text-secondary" title="Просмотр">
                                        <i class="ri ri-eye-line"></i>
                                    </a>
                                    <a href="{{ route('orders.edit', $order) }}" class="btn btn-sm btn-icon btn-text-secondary" title="Редактировать">
                                        <i class="ri ri-edit-line"></i>
                                    </a>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-icon btn-text-secondary text-danger js-cancel-single"
                                        data-order-id="{{ $order->id }}"
                                        title="Отменить"
                                    >
                                        <i class="ri ri-close-circle-line"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted">Записей пока нет. Создайте первую, чтобы заполнить расписание.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="text-muted small">Показано {{ $orders->count() }} из {{ $orders->total() }}</div>
            {{ $orders->links('pagination::bootstrap-5') }}
        </div>
    </form>

    <form method="POST" action="" id="single-cancel-form" class="d-none">
        @csrf
    </form>

    <!-- Быстрое создание -->
    <div class="modal fade" id="quickCreateModal" tabindex="-1" aria-labelledby="quickCreateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="quickCreateModalLabel">Быстрое создание записи</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('orders.quick-store') }}" id="quick-create-form">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted">Укажите телефон клиента и время визита. Если клиента нет в базе, мы создадим его автоматически.</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="quick_master_name"
                                        value="{{ auth()->user()?->name ?? 'Вы' }}"
                                        readonly
                                    />
                                    <label for="quick_master_name">Мастер</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <input type="datetime-local" class="form-control" id="quick_scheduled_at" name="scheduled_at" required />
                                    <label for="quick_scheduled_at">Дата и время</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="quick_client_phone"
                                        name="client_phone"
                                        placeholder="+7(999)999-99-99"
                                        data-phone-mask
                                        required
                                    />
                                    <label for="quick_client_phone">Телефон клиента</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <input type="text" class="form-control" id="quick_client_name" name="client_name" placeholder="Имя" />
                                    <label for="quick_client_name">Имя клиента</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating form-floating-outline">
                                    <textarea class="form-control" id="quick_note" name="note" style="height: 120px"></textarea>
                                    <label for="quick_note">Комментарий</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отменить</button>
                        <button type="submit" class="btn btn-primary">Создать</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @include('components.phone-mask-script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectAll = document.getElementById('select-all');
            const checkboxes = document.querySelectorAll('.order-checkbox');
            const bulkForm = document.getElementById('orders-bulk-form');
            const bulkCancelBtn = document.getElementById('bulk-cancel-btn');
            const cancelButtons = document.querySelectorAll('.js-cancel-single');
            const singleCancelForm = document.getElementById('single-cancel-form');

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    checkboxes.forEach(function (checkbox) {
                        checkbox.checked = selectAll.checked;
                    });
                });
            }

            if (bulkForm && bulkCancelBtn) {
                bulkForm.addEventListener('submit', function (event) {
                    const action = event.submitter ? event.submitter.value : null;
                    if (action === 'cancel') {
                        const selected = Array.from(checkboxes).some(function (checkbox) { return checkbox.checked; });
                        if (!selected) {
                            event.preventDefault();
                            window.alert('Выберите хотя бы одну запись для отмены.');
                            return false;
                        }
                        if (!window.confirm('Отменить выбранные записи?')) {
                            event.preventDefault();
                            return false;
                        }
                    }
                });
            }

            cancelButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    const orderId = this.dataset.orderId;
                    if (!orderId) {
                        return;
                    }
                    if (!window.confirm('Вы уверены, что хотите отменить эту запись?')) {
                        return;
                    }
                    singleCancelForm.setAttribute('action', "{{ url('orders') }}/" + orderId + '/cancel');
                    singleCancelForm.submit();
                });
            });
        });
    </script>
@endsection

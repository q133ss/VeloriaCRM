@php
    $selectedServices = collect(old('services', collect($order->services ?? [])->pluck('id')->filter()->all()))
        ->map(fn ($id) => (int) $id)
        ->all();
    $scheduledAt = old('scheduled_at', optional($order->scheduled_at)->format('Y-m-d\TH:i'));
    $selectedStatus = old('status', $order->status ?? 'new');
    $clientPhone = old('client_phone', $client->phone ?? '');
    $clientName = old('client_name', $client->name ?? '');
    $clientEmail = old('client_email', $client->email ?? '');
    $totalPrice = old('total_price', $order->total_price ?? null);
    $statusOptions = \App\Models\Order::statusLabels();
    $currentMaster = auth()->user();
@endphp

<div class="row g-4">
    <div class="col-md-4">
        <div class="form-floating form-floating-outline">
            <input
                type="text"
                class="form-control"
                id="master_name"
                value="{{ $order->master?->name ?? $currentMaster?->name ?? 'Не указан' }}"
                readonly
            />
            <label for="master_name">Мастер</label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-floating form-floating-outline">
            <input
                type="text"
                class="form-control"
                id="client_phone"
                name="client_phone"
                value="{{ $clientPhone }}"
                placeholder="+7(999)999-99-99"
                data-phone-mask
                required
            />
            <label for="client_phone">Телефон клиента</label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-floating form-floating-outline">
            <input
                type="text"
                class="form-control"
                id="client_name"
                name="client_name"
                value="{{ $clientName }}"
                placeholder="Имя клиента"
            />
            <label for="client_name">Имя клиента</label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-floating form-floating-outline">
            <input
                type="email"
                class="form-control"
                id="client_email"
                name="client_email"
                value="{{ $clientEmail }}"
                placeholder="email@example.com"
            />
            <label for="client_email">Email клиента</label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-floating form-floating-outline">
            <input
                type="datetime-local"
                class="form-control"
                id="scheduled_at"
                name="scheduled_at"
                value="{{ $scheduledAt }}"
                required
            />
            <label for="scheduled_at">Запланированная дата и время</label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-floating form-floating-outline">
            <select class="form-select" id="status" name="status" required>
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" {{ $selectedStatus === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <label for="status">Статус</label>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Выбранные услуги</h5>
                <small class="text-muted">Отметьте, что войдёт в заказ</small>
            </div>
            <div class="card-body">
                @if($services->isEmpty())
                    <p class="text-muted mb-0">Услуги ещё не созданы. Добавьте их в разделе «Услуги».</p>
                @else
                    <div class="row g-3" id="services-list">
                        @foreach($services as $service)
                            <div class="col-md-6">
                                <div class="form-check custom-option custom-option-basic">
                                    <label class="form-check-label custom-option-content" for="service-{{ $service->id }}">
                                        <input
                                            type="checkbox"
                                            class="form-check-input service-checkbox"
                                            id="service-{{ $service->id }}"
                                            name="services[]"
                                            value="{{ $service->id }}"
                                            data-price="{{ $service->base_price }}"
                                            data-duration="{{ $service->duration_min }}"
                                            {{ in_array($service->id, $selectedServices, true) ? 'checked' : '' }}
                                        />
                                        <span class="custom-option-body">
                                            <span class="custom-option-title d-flex align-items-center justify-content-between">
                                                <span>{{ $service->name }}</span>
                                                <span class="badge bg-label-primary">{{ number_format($service->base_price, 2, '.', ' ') }} ₽</span>
                                            </span>
                                            <small class="text-muted">Длительность: {{ $service->duration_min }} мин</small>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Рекомендации ИИ</h5>
                <span class="badge bg-label-info">Заглушка</span>
            </div>
            <div class="card-body">
                @forelse($recommendedServices as $recommendation)
                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <strong>{{ $recommendation['name'] }}</strong>
                            @if(!empty($recommendation['id']))
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary js-recommendation"
                                    data-service-id="{{ $recommendation['id'] }}"
                                >
                                    Добавить
                                </button>
                            @else
                                <span class="badge bg-secondary">Скоро</span>
                            @endif
                        </div>
                        <p class="text-muted small mb-0">{{ $recommendation['description'] ?? 'ИИ предложит услугу на основе поведения клиента.' }}</p>
                    </div>
                @empty
                    <p class="text-muted">Пока нет рекомендаций.</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="form-floating form-floating-outline mb-4">
            <input
                type="number"
                step="0.01"
                min="0"
                class="form-control"
                id="total_price"
                name="total_price"
                value="{{ $totalPrice }}"
            />
            <label for="total_price">Итоговая сумма (₽)</label>
        </div>
        <div class="card">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-6">Предварительная сумма</dt>
                    <dd class="col-6 text-end" id="summary-price">0 ₽</dd>
                    <dt class="col-6">Прогноз времени</dt>
                    <dd class="col-6 text-end" id="summary-duration">0 мин</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="form-floating form-floating-outline h-100">
            <textarea class="form-control" id="note" name="note" style="height: 160px">{{ old('note', $order->note) }}</textarea>
            <label for="note">Заметка для мастера</label>
        </div>
    </div>
</div>

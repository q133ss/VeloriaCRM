<style>
    #quickCreateModal .quick-client-layer {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    #quickCreateModal #quick-client-results,
    #quickCreateModal #quick-client-suggestions {
        position: static;
        max-height: 260px;
        overflow-y: auto;
        z-index: 1;
        margin-top: 0;
        background: var(--bs-paper-bg, var(--bs-body-bg));
        box-shadow: 0 1rem 2rem rgba(15, 23, 42, 0.28);
    }
</style>

<div class="modal fade" id="quickCreateModal" tabindex="-1" aria-labelledby="quickCreateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickCreateModalLabel">Быстрое создание записи</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quick-create-form" onsubmit="return false;">
                <div class="modal-body">
                    <p class="text-muted">Сначала найдите клиента по имени или телефону. Если нужного человека нет в истории, ниже можно сразу создать нового.</p>
                    <input type="hidden" id="quick_master_name" value="{{ auth()->user()?->name ?? 'Вы' }}" />
                    <input type="hidden" id="quick_client_id" name="client_id" />
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="quick-client-layer">
                                <div class="form-floating form-floating-outline">
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="quick_client_search"
                                        placeholder="Анна, +7..., Иван"
                                        autocomplete="off"
                                    />
                                    <label for="quick_client_search">Найти существующего клиента</label>
                                </div>
                                <div id="quick-client-results" class="list-group list-group-flush border rounded-3 shadow-sm mt-2 d-none"></div>
                            </div>
                            <div class="form-text">Поиск по имени и телефону. В списке сначала показываются недавние клиенты.</div>
                            <div id="quick-selected-client" class="alert alert-primary d-none mt-2 mb-0"></div>
                        </div>
                        <div class="col-md-6">
                            @include('components.veloria-datetime-field', [
                                'id' => 'quick_scheduled_at',
                                'name' => 'scheduled_at',
                                'label' => 'Дата и время',
                                'required' => true,
                                'helper' => 'Клик по полю открывает календарь. Ниже можно сразу выбрать день и удобное время.',
                                'timeSlots' => ['09:00', '11:00', '13:00', '15:00', '18:00'],
                            ])
                        </div>
                        <div class="col-md-6">
                            <div class="quick-client-layer">
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
                                    <label for="quick_client_phone">Телефон нового клиента</label>
                                </div>
                                <div id="quick-client-suggestions" class="list-group list-group-flush border rounded-3 shadow-sm mt-2 d-none"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating form-floating-outline">
                                <input type="text" class="form-control" id="quick_client_name" name="client_name" placeholder="Имя" />
                                <label for="quick_client_name">Имя клиента</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Услуги</label>
                            <div class="row g-2" id="quick-services-container">
                                <div class="col-12 text-muted">Загрузка услуг...</div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
                                <span>Предварительная сумма</span>
                                <span id="quick-services-summary">0 ₽</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-floating form-floating-outline">
                                <textarea class="form-control" id="quick_note" name="note" style="height: 120px"></textarea>
                                <label for="quick_note">Комментарий</label>
                            </div>
                        </div>
                    </div>
                    <div id="quick-create-errors" class="mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отменить</button>
                    <button type="submit" class="btn btn-primary">Создать</button>
                </div>
            </form>
        </div>
    </div>
</div>

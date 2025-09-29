@php
    $client = $client ?? null;
    $tagsValue = old('tags', $client ? implode(', ', $client->tags ?? []) : '');
    $allergiesValue = old('allergies', $client ? implode(', ', $client->allergies ?? []) : '');
    $preferencesValue = old('preferences', $client ? implode(', ', $client->preferences ?? []) : '');
@endphp

<div class="row g-4">
    <div class="col-md-6">
        <div class="form-floating form-floating-outline">
            <input
                type="text"
                class="form-control @error('name') is-invalid @enderror"
                id="client-name"
                name="name"
                value="{{ old('name', $client->name ?? '') }}"
                required
            />
            <label for="client-name">Имя</label>
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-floating form-floating-outline">
            <input
                type="text"
                class="form-control @error('phone') is-invalid @enderror"
                id="client-phone"
                name="phone"
                value="{{ old('phone', $client->phone ?? '') }}"
                placeholder="+7 (999) 000-00-00"
            />
            <label for="client-phone">Телефон</label>
            @error('phone')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-floating form-floating-outline">
            <input
                type="email"
                class="form-control @error('email') is-invalid @enderror"
                id="client-email"
                name="email"
                value="{{ old('email', $client->email ?? '') }}"
                placeholder="name@example.com"
            />
            <label for="client-email">Email</label>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-floating form-floating-outline">
            <input
                type="date"
                class="form-control @error('birthday') is-invalid @enderror"
                id="client-birthday"
                name="birthday"
                value="{{ old('birthday', $client?->birthday?->format('Y-m-d')) }}"
            />
            <label for="client-birthday">День рождения</label>
            @error('birthday')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-floating form-floating-outline">
            <input
                type="text"
                class="form-control @error('loyalty_level') is-invalid @enderror"
                id="client-loyalty"
                name="loyalty_level"
                value="{{ old('loyalty_level', $client->loyalty_level ?? '') }}"
            />
            <label for="client-loyalty">Уровень лояльности</label>
            @error('loyalty_level')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-floating form-floating-outline">
            <input
                type="datetime-local"
                class="form-control @error('last_visit_at') is-invalid @enderror"
                id="client-last-visit"
                name="last_visit_at"
                value="{{ old('last_visit_at', $client?->last_visit_at?->format('Y-m-d\\TH:i')) }}"
            />
            <label for="client-last-visit">Последний визит</label>
            @error('last_visit_at')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-floating form-floating-outline">
            <input
                type="text"
                class="form-control @error('tags') is-invalid @enderror"
                id="client-tags"
                name="tags"
                value="{{ $tagsValue }}"
                placeholder="VIP, Постоянный"
            />
            <label for="client-tags">Теги (через запятую)</label>
            @error('tags')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-floating form-floating-outline">
            <input
                type="text"
                class="form-control @error('allergies') is-invalid @enderror"
                id="client-allergies"
                name="allergies"
                value="{{ $allergiesValue }}"
                placeholder="Пыльца, Лаванда"
            />
            <label for="client-allergies">Аллергии (через запятую)</label>
            @error('allergies')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-floating form-floating-outline">
            <input
                type="text"
                class="form-control @error('preferences') is-invalid @enderror"
                id="client-preferences"
                name="preferences"
                value="{{ $preferencesValue }}"
                placeholder="Теплый тон, Нейтральный дизайн"
            />
            <label for="client-preferences">Предпочтения (через запятую)</label>
            @error('preferences')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-12">
        <div class="form-floating form-floating-outline">
            <textarea
                class="form-control @error('notes') is-invalid @enderror"
                id="client-notes"
                name="notes"
                style="height: 140px"
            >{{ old('notes', $client->notes ?? '') }}</textarea>
            <label for="client-notes">Заметки</label>
            @error('notes')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

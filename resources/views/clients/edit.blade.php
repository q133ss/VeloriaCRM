@extends('layouts.app')

@section('title', 'Редактирование клиента')

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">Редактирование: {{ $client->name }}</h4>
            <p class="text-muted mb-0">Обновите контактные данные и персональные настройки клиента.</p>
        </div>
        <a href="{{ route('clients.show', $client) }}" class="btn btn-outline-secondary">
            <i class="ri ri-arrow-left-line me-1"></i>
            Назад к карточке
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <h6 class="alert-heading mb-2">Проверьте введённые данные</h6>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('clients.update', $client) }}" method="POST" class="card mb-3">
        @csrf
        @method('PUT')
        <div class="card-body">
            @include('clients.partials.form', ['client' => $client])
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('clients.show', $client) }}" class="btn btn-outline-secondary">Отмена</a>
            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
        </div>
    </form>

    <form
        action="{{ route('clients.destroy', $client) }}"
        method="POST"
        class="d-inline"
        onsubmit="return confirm('Удалить клиента {{ $client->name }}?');"
    >
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger">Удалить клиента</button>
    </form>
@endsection

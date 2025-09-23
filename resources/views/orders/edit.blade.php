@extends('layouts.app')

@section('title', 'Редактирование записи')

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">Редактирование записи</h4>
            <p class="text-muted mb-0">Обновите детали посещения клиента.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('orders.show', $order) }}" class="btn btn-outline-secondary">
                <i class="ri ri-eye-line me-1"></i>
                Просмотр
            </a>
            <a href="{{ route('orders.index') }}" class="btn btn-light">
                <i class="ri ri-list-check me-1"></i>
                Список записей
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Проверьте форму:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('orders.update', $order) }}" class="card p-4">
        @csrf
        @method('PUT')

        @include('orders.partials.form', [
            'order' => $order,
            'services' => $services,
            'masters' => $masters,
            'client' => $client,
            'recommendedServices' => $recommendedServices,
        ])

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('orders.show', $order) }}" class="btn btn-outline-secondary">Отмена</a>
            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
        </div>
    </form>
@endsection

@section('scripts')
    @include('orders.partials.form-scripts')
@endsection

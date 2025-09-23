@extends('layouts.app')

@section('title', 'Новая запись')

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">Создание записи</h4>
            <p class="text-muted mb-0">Укажите клиента, время и необходимые услуги.</p>
        </div>
        <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary">
            <i class="ri ri-arrow-go-back-line me-1"></i>
            Вернуться к списку
        </a>
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

    <form method="POST" action="{{ route('orders.store') }}" class="card p-4">
        @csrf
        @include('orders.partials.form', [
            'order' => $order,
            'services' => $services,
            'masters' => $masters,
            'client' => $client,
            'recommendedServices' => $recommendedServices,
        ])

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary">Отмена</a>
            <button type="submit" class="btn btn-primary">Создать запись</button>
        </div>
    </form>
@endsection

@section('scripts')
    @include('orders.partials.form-scripts')
@endsection

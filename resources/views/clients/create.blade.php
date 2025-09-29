@extends('layouts.app')

@section('title', 'Новый клиент')

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">Добавление клиента</h4>
            <p class="text-muted mb-0">Заполните контактные данные и предпочтения клиента.</p>
        </div>
        <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">
            <i class="ri ri-arrow-left-line me-1"></i>
            Вернуться к списку
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

    <form action="{{ route('clients.store') }}" method="POST" class="card">
        @csrf
        <div class="card-body">
            @include('clients.partials.form')
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">Отмена</a>
            <button type="submit" class="btn btn-primary">Сохранить</button>
        </div>
    </form>
@endsection

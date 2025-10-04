@extends('layouts.app')

@section('content')
    <div class="max-w-xl mx-auto py-10">
        <h1 class="text-2xl font-semibold mb-6">Как вы себя чувствуете?</h1>

        {{-- Простой виджет, который мастер может открыть из уведомления. --}}
        <form id="master-mood-form" class="space-y-4">
            <p class="text-gray-600">Выберите вариант, чтобы помочь ИИ понимать вашу нагрузку.</p>

            @foreach(\App\Models\MasterMood::MOOD_OPTIONS as $value => $label)
                <label class="flex items-center gap-3 p-4 border rounded-lg hover:bg-indigo-50 cursor-pointer">
                    <input type="radio" name="mood" value="{{ $value }}" class="h-4 w-4" required>
                    <span class="text-lg">{{ $label }}</span>
                </label>
            @endforeach

            <button type="submit"
                    class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                Отправить
            </button>

            <p id="master-mood-success" class="text-green-600 hidden">Спасибо, ответ сохранён!</p>
            <p id="master-mood-error" class="text-red-600 hidden">Не удалось отправить ответ, попробуйте позже.</p>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('master-mood-form');
            const success = document.getElementById('master-mood-success');
            const error = document.getElementById('master-mood-error');

            // Демонстрационный submit через fetch. Можно встроить в SPA/мобильное приложение.
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                success.classList.add('hidden');
                error.classList.add('hidden');

                const formData = new FormData(form);
                const mood = formData.get('mood');

                try {
                    const response = await fetch('/api/v1/master-moods', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                        body: JSON.stringify({ mood }),
                    });

                    if (!response.ok) {
                        throw new Error('Request failed');
                    }

                    form.reset();
                    success.classList.remove('hidden');
                } catch (e) {
                    console.error(e);
                    error.classList.remove('hidden');
                }
            });
        });
    </script>
@endpush

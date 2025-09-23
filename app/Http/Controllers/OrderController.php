<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Order;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'period' => $request->input('period', 'this_week'),
            'status' => $request->input('status', 'all'),
            'search' => $request->input('search'),
        ];

        /** @var LengthAwarePaginator $orders */
        $orders = Order::with(['client', 'master'])
            ->withFilter($filters)
            ->orderByDesc('scheduled_at')
            ->paginate(12)
            ->withQueryString();

        $periodOptions = [
            'today' => 'Сегодня',
            'tomorrow' => 'Завтра',
            'this_week' => 'Текущая неделя',
            'next_week' => 'Следующая неделя',
            'this_month' => 'Текущий месяц',
            'next_month' => 'Следующий месяц',
            'all' => 'За всё время',
        ];

        $statusOptions = ['all' => 'Все статусы'] + Order::statusLabels();
        $settings = $this->resolveUserSettings();

        $masters = User::orderBy('name')->get();

        return view('orders.index', [
            'orders' => $orders,
            'filters' => $filters,
            'periodOptions' => $periodOptions,
            'statusOptions' => $statusOptions,
            'reminderMessage' => optional($settings)->reminder_message,
            'masters' => $masters,
        ]);
    }

    public function create(Request $request): View
    {
        $services = Service::orderBy('name')->get();
        $masters = User::orderBy('name')->get();
        $client = null;

        if ($request->filled('client_id')) {
            $client = User::find($request->input('client_id'));
        } elseif ($request->filled('client_phone')) {
            $client = User::where('phone', $this->normalizePhone($request->input('client_phone')))->first();
        }

        $recommendedServices = $this->buildRecommendedServices($client, $services);

        return view('orders.create', [
            'order' => new Order(),
            'services' => $services,
            'masters' => $masters,
            'client' => $client,
            'recommendedServices' => $recommendedServices,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateOrder($request);

        $order = DB::transaction(function () use ($validated) {
            $client = $this->resolveClient(
                $validated['client_phone'],
                Arr::get($validated, 'client_name'),
                Arr::get($validated, 'client_email')
            );

            $services = $this->collectServices(Arr::get($validated, 'services', []));

            $servicePayload = $services->map(function (Service $service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'price' => (float) $service->base_price,
                    'duration' => (int) $service->duration_min,
                ];
            })->values()->all();

            $totalPrice = Arr::get($validated, 'total_price');
            if ($totalPrice === null) {
                $totalPrice = $services->sum('base_price');
            }

            $recommended = $this->buildRecommendedServices($client, Service::orderBy('name')->get());

            return Order::create([
                'master_id' => $validated['master_id'],
                'client_id' => $client->id,
                'services' => $servicePayload,
                'scheduled_at' => Carbon::parse($validated['scheduled_at']),
                'note' => Arr::get($validated, 'note'),
                'duration_forecast' => $services->sum('duration_min') ?: null,
                'total_price' => $totalPrice ?? 0,
                'status' => $validated['status'],
                'source' => Arr::get($validated, 'source', 'manual'),
                'recommended_services' => $recommended->map(function ($service) {
                    return Arr::only($service, ['id', 'name', 'description']);
                })->all(),
            ]);
        });

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Запись успешно создана.');
    }

    public function show(Order $order): View
    {
        $order->loadMissing(['client', 'master']);
        $hasProAccess = $this->userHasProAccess();
        $reminderMessage = optional($this->resolveUserSettings())->reminder_message;

        return view('orders.show', [
            'order' => $order,
            'hasProAccess' => $hasProAccess,
            'reminderMessage' => $reminderMessage,
        ]);
    }

    public function edit(Order $order): View
    {
        $order->loadMissing(['client', 'master']);
        $services = Service::orderBy('name')->get();
        $masters = User::orderBy('name')->get();
        $client = $order->client;
        $recommendedServices = $this->buildRecommendedServices($client, $services);

        return view('orders.edit', [
            'order' => $order,
            'services' => $services,
            'masters' => $masters,
            'client' => $client,
            'recommendedServices' => $recommendedServices,
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $validated = $this->validateOrder($request, $order->id);

        DB::transaction(function () use ($validated, $order) {
            $client = $this->resolveClient(
                $validated['client_phone'],
                Arr::get($validated, 'client_name'),
                Arr::get($validated, 'client_email')
            );

            $services = $this->collectServices(Arr::get($validated, 'services', []));

            $servicePayload = $services->map(function (Service $service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'price' => (float) $service->base_price,
                    'duration' => (int) $service->duration_min,
                ];
            })->values()->all();

            $totalPrice = Arr::get($validated, 'total_price');
            if ($totalPrice === null) {
                $totalPrice = $services->sum('base_price');
            }

            $order->update([
                'master_id' => $validated['master_id'],
                'client_id' => $client->id,
                'services' => $servicePayload,
                'scheduled_at' => Carbon::parse($validated['scheduled_at']),
                'note' => Arr::get($validated, 'note'),
                'duration_forecast' => $services->sum('duration_min') ?: null,
                'total_price' => $totalPrice ?? 0,
                'status' => $validated['status'],
            ]);
        });

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Запись обновлена.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        $order->delete();

        return redirect()
            ->route('orders.index')
            ->with('status', 'Запись удалена.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'orders' => ['required', 'array'],
            'orders.*' => ['integer', 'exists:orders,id'],
            'action' => ['required', Rule::in(['confirm', 'remind', 'cancel'])],
        ]);

        $orders = Order::whereIn('id', $validated['orders'])->get();
        $now = Carbon::now();

        $message = match ($validated['action']) {
            'confirm' => 'Выбранные записи подтверждены.',
            'remind' => 'Напоминания отмечены как отправленные.',
            'cancel' => 'Выбранные записи отменены.',
        };

        DB::transaction(function () use ($orders, $validated, $now) {
            foreach ($orders as $order) {
                switch ($validated['action']) {
                    case 'confirm':
                        $order->update([
                            'status' => 'confirmed',
                            'confirmed_at' => $now,
                        ]);
                        break;
                    case 'remind':
                        $order->update([
                            'reminded_at' => $now,
                            'is_reminder_sent' => true,
                        ]);
                        break;
                    case 'cancel':
                        $order->update([
                            'status' => 'cancelled',
                            'cancelled_at' => $now,
                        ]);
                        break;
                }
            }
        });

        $settings = $this->resolveUserSettings();

        return redirect()
            ->back()
            ->with('status', $message)
            ->with('reminder_text', optional($settings)->reminder_message);
    }

    public function quickStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'master_id' => ['required', 'exists:users,id'],
            'client_phone' => ['required', 'string', 'min:5'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'scheduled_at' => ['required', 'date'],
            'note' => ['nullable', 'string'],
        ]);

        $order = DB::transaction(function () use ($validated) {
            $client = $this->resolveClient($validated['client_phone'], Arr::get($validated, 'client_name'));
            $services = Service::orderBy('name')->take(3)->get();
            $recommended = $this->buildRecommendedServices($client, $services);

            return Order::create([
                'master_id' => $validated['master_id'],
                'client_id' => $client->id,
                'services' => [],
                'scheduled_at' => Carbon::parse($validated['scheduled_at']),
                'note' => Arr::get($validated, 'note'),
                'total_price' => 0,
                'status' => 'new',
                'recommended_services' => $recommended->map(fn ($item) => Arr::only($item, ['id', 'name', 'description']))->all(),
            ]);
        });

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Запись создана через быстрое создание.');
    }

    public function complete(Order $order): RedirectResponse
    {
        $now = Carbon::now();
        $duration = null;

        if ($order->actual_started_at) {
            $duration = $order->actual_started_at->diffInMinutes($now);
        }

        $order->update([
            'status' => 'completed',
            'actual_finished_at' => $now,
            'duration' => $duration,
        ]);

        return redirect()
            ->back()
            ->with('status', 'Запись завершена.');
    }

    public function start(Order $order): RedirectResponse
    {
        $now = Carbon::now();

        $order->update([
            'status' => 'in_progress',
            'actual_started_at' => $now,
        ]);

        return redirect()
            ->back()
            ->with('status', 'Начало работы зафиксировано.');
    }

    public function remind(Order $order): RedirectResponse
    {
        $now = Carbon::now();

        $order->update([
            'reminded_at' => $now,
            'is_reminder_sent' => true,
        ]);

        $settings = $this->resolveUserSettings();

        return redirect()
            ->back()
            ->with('status', 'Напоминание отмечено. Не забудьте отправить клиенту сообщение!')
            ->with('reminder_text', optional($settings)->reminder_message);
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $order->update([
            'status' => 'cancelled',
            'cancelled_at' => Carbon::now(),
            'cancellation_reason' => Arr::get($validated, 'reason'),
        ]);

        return redirect()
            ->route('orders.index')
            ->with('status', 'Запись отменена.');
    }

    public function reschedule(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'scheduled_at' => ['required', 'date'],
        ]);

        $previousDate = $order->scheduled_at;
        $order->update([
            'rescheduled_from' => $previousDate && !$order->rescheduled_from ? $previousDate : $order->rescheduled_from,
            'scheduled_at' => Carbon::parse($validated['scheduled_at']),
            'reschedule_count' => $order->reschedule_count + 1,
        ]);

        return redirect()
            ->back()
            ->with('status', 'Запись перенесена.');
    }

    protected function validateOrder(Request $request, ?int $orderId = null): array
    {
        return $request->validate([
            'master_id' => ['required', 'exists:users,id'],
            'client_phone' => ['required', 'string', 'min:5'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'client_email' => ['nullable', 'email'],
            'scheduled_at' => ['required', 'date'],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer', 'exists:services,id'],
            'note' => ['nullable', 'string'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(array_keys(Order::statusLabels()))],
            'source' => ['nullable', 'string', 'max:50'],
        ]);
    }

    protected function collectServices(array $serviceIds): Collection
    {
        if (empty($serviceIds)) {
            return new Collection();
        }

        return Service::whereIn('id', $serviceIds)->get();
    }

    protected function resolveClient(string $phone, ?string $name = null, ?string $email = null): User
    {
        $normalizedPhone = $this->normalizePhone($phone);

        $user = User::where('phone', $normalizedPhone)->first();

        if (! $user) {
            $user = User::create([
                'name' => $name ?: 'Клиент ' . Str::substr($normalizedPhone, -4),
                'phone' => $normalizedPhone,
                'email' => $email,
                'password' => Str::random(16),
            ]);
        } else {
            $user->forceFill([
                'name' => $name ?: $user->name,
                'email' => $email ?: $user->email,
                'phone' => $normalizedPhone,
            ])->save();
        }

        Client::updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $name ?: $user->name,
                'phone' => $normalizedPhone,
                'email' => $email,
            ]
        );

        return $user;
    }

    protected function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]+/', '', $phone);

        if (! $digits) {
            return trim($phone);
        }

        if (strlen($digits) === 10) {
            $digits = '7' . $digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '8')) {
            $digits = '7' . substr($digits, 1);
        }

        if (! str_starts_with($digits, '7') && ! str_starts_with($digits, '8')) {
            $digits = '7' . $digits;
        }

        return '+' . $digits;
    }

    protected function buildRecommendedServices(?User $client, Collection $services): BaseCollection
    {
        $suggestions = $services->take(3);

        if ($suggestions->isEmpty()) {
            return collect([
                [
                    'id' => null,
                    'name' => 'Персонализированная консультация',
                    'description' => 'ИИ предложит услугу исходя из предпочтений клиента (заглушка).',
                ],
            ]);
        }

        return $suggestions->map(function (Service $service, int $index) use ($client) {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'description' => 'Заглушка рекомендации ИИ на основе предыдущих визитов ' . ($client?->name ?? 'клиента') . '.',
            ];
        });
    }

    protected function resolveUserSettings(): ?Setting
    {
        $userId = Auth::id();

        if (! $userId) {
            return null;
        }

        return Setting::where('user_id', $userId)->first();
    }

    protected function userHasProAccess(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->plans()->whereIn('name', ['PRO', 'Pro', 'Elite', 'ELITE'])->exists();
    }
}

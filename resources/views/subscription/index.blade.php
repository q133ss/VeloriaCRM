@extends('layouts.app')

@section('title', __('subscription.title'))

@section('meta')
    <style>
        .subscription-current-card {
            background: linear-gradient(135deg, rgba(105, 108, 255, 0.12), rgba(3, 195, 236, 0.08));
            border: 1px solid rgba(105, 108, 255, 0.18);
        }

        .subscription-plan-card {
            border: 1px solid transparent;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .subscription-plan-card:hover {
            border-color: rgba(105, 108, 255, 0.4);
            box-shadow: 0 1.5rem 3rem -1.5rem rgba(105, 108, 255, 0.35);
        }

        .subscription-plan-price {
            font-size: 2rem;
            font-weight: 700;
        }

        .subscription-plan-features li + li {
            margin-top: 0.65rem;
        }

        .subscription-comparison-table td,
        .subscription-comparison-table th {
            vertical-align: middle;
        }

        .subscription-keep-lose ul {
            padding-left: 1.5rem;
        }

        .subscription-keep-lose li + li {
            margin-top: 0.5rem;
        }
    </style>
@endsection

@section('content')
    @if (session('status'))
        <div class="alert alert-success alert-dismissible" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card subscription-current-card mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-4">
                <div class="flex-grow-1">
                    <p class="text-uppercase text-muted fw-medium mb-1 small">{{ __('subscription.current_plan.title') }}</p>
                    <h3 class="mb-3">{{ __('subscription.subtitle') }}</h3>

                    @if ($currentPlan)
                        @php
                            $endsAt = $currentPlan->pivot->ends_at;
                            $planDetailsEntry = $planDetails->get($currentPlan->slug, []);
                            if ($endsAt && ! $endsAt instanceof \Carbon\CarbonInterface) {
                                $endsAt = \Illuminate\Support\Carbon::parse($endsAt);
                            }
                        @endphp
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="avatar avatar-lg bg-primary text-white">
                                <span class="avatar-initial fw-semibold text-uppercase">{{ \Illuminate\Support\Str::limit($currentPlan->slug, 2, '') }}</span>
                            </div>
                            <div>
                                <h4 class="mb-1">{{ \Illuminate\Support\Arr::get($planDetailsEntry, 'name', ucfirst($currentPlan->slug)) }}</h4>
                                <div class="text-muted small">{{ \Illuminate\Support\Arr::get($planDetailsEntry, 'tagline') }}</div>
                            </div>
                        </div>

                        <div class="border rounded p-3 bg-white">
                            @if ($endsAt)
                                <div class="d-flex align-items-center gap-2">
                                    <i class="ri ri-time-line text-primary"></i>
                                    <span class="small text-muted">{{ __('subscription.current_plan.active_until', ['date' => $endsAt?->copy()->locale(app()->getLocale())->isoFormat('D MMMM YYYY')]) }}</span>
                                </div>
                            @else
                                <div class="small text-muted">{{ __('subscription.current_plan.free_plan') }}</div>
                            @endif
                        </div>
                    @else
                        <p class="text-muted mb-0">{{ __('subscription.current_plan.no_plan') }}</p>
                    @endif
                </div>

                <div class="d-flex flex-column gap-3 align-self-stretch">
                    <form method="POST" action="{{ route('subscription.cancel') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger px-4" {{ $activePlan ? '' : 'disabled' }}>
                            {{ __('subscription.actions.cancel') }}
                        </button>
                    </form>
                    <small class="text-muted">{{ __('subscription.cancel.note') }}</small>
                </div>
            </div>

            <hr class="my-4">

            <div class="subscription-keep-lose">
                <h5 class="mb-3">{{ __('subscription.cancel.title') }}</h5>
                <div class="row g-4">
                    <div class="col-md-6">
                        <h6 class="fw-semibold mb-3">{{ __('subscription.cancel.keep_title') }}</h6>
                        <ul class="text-muted mb-0">
                            @foreach (__('subscription.cancel.keep') as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-semibold mb-3">{{ __('subscription.cancel.lose_title') }}</h6>
                        <ul class="text-muted mb-0">
                            @foreach (__('subscription.cancel.lose') as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach ($plans as $plan)
            @php
                $details = $planDetails->get($plan->slug, []);
                $isCurrent = $currentPlan && $plan->getKey() === $currentPlan->getKey();
                $isUpgrade = ! $isCurrent && (! $currentPlan || $plan->price > $currentPlan->price);
                $price = $plan->price > 0 ? number_format($plan->price, 0, '', ' ') : null;
            @endphp
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card subscription-plan-card h-100 {{ $isCurrent ? 'border-primary shadow-sm' : '' }}">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="mb-1">{{ \Illuminate\Support\Arr::get($details, 'name', ucfirst($plan->slug)) }}</h5>
                                <p class="text-muted mb-0">{{ \Illuminate\Support\Arr::get($details, 'tagline') }}</p>
                            </div>
                            @if ($badge = \Illuminate\Support\Arr::get($details, 'badge'))
                                <span class="badge bg-label-primary">{{ $badge }}</span>
                            @endif
                        </div>

                        <div class="mb-3">
                            @if ($price)
                                <div class="subscription-plan-price">{{ $currencySymbol }}{{ $price }}</div>
                                <div class="text-muted">{{ __('subscription.billing_period') }}</div>
                            @else
                                <div class="fw-semibold">{{ __('subscription.current_plan.free_plan') }}</div>
                            @endif
                        </div>

                        @if (! empty($details['features']))
                            <ul class="subscription-plan-features list-unstyled flex-grow-1 mb-4">
                                @foreach ($details['features'] as $feature)
                                    <li class="d-flex align-items-center gap-2">
                                        <i class="ri ri-check-line text-success"></i>
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="mt-auto">
                            @if ($isCurrent)
                                <button class="btn btn-outline-secondary w-100" disabled>
                                    {{ __('subscription.actions.current') }}
                                </button>
                            @elseif ($isUpgrade && $yooKassaEnabled)
                                <form method="POST" action="{{ route('subscription.upgrade') }}">
                                    @csrf
                                    <input type="hidden" name="plan_id" value="{{ $plan->getKey() }}">
                                    <button type="submit" class="btn btn-primary w-100">
                                        {{ __('subscription.actions.upgrade', ['plan' => \Illuminate\Support\Arr::get($details, 'name', ucfirst($plan->slug))]) }}
                                    </button>
                                </form>
                            @else
                                <button class="btn btn-outline-secondary w-100" disabled>
                                    {{ __('subscription.actions.upgrade', ['plan' => \Illuminate\Support\Arr::get($details, 'name', ucfirst($plan->slug))]) }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-3">{{ __('subscription.comparison_title') }}</h5>
            <div class="table-responsive">
                <table class="table align-middle subscription-comparison-table">
                    <thead>
                        <tr>
                            <th class="w-50">{{ __('subscription.comparison_feature') }}</th>
                            @foreach ($plans as $plan)
                                @php $details = $planDetails->get($plan->slug, []); @endphp
                                <th class="text-center">{{ \Illuminate\Support\Arr::get($details, 'name', ucfirst($plan->slug)) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($comparison as $row)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ \Illuminate\Support\Arr::get($row, 'feature') }}</div>
                                    <div class="text-muted small">{{ \Illuminate\Support\Arr::get($row, 'description') }}</div>
                                </td>
                                @foreach ($plans as $plan)
                                    @php
                                        $plansRow = \Illuminate\Support\Arr::get($row, 'plans', []);
                                        $value = $plansRow[$plan->slug] ?? false;
                                    @endphp
                                    <td class="text-center">
                                        @if ($value === true)
                                            <i class="ri ri-check-line text-success"></i>
                                        @elseif ($value === false)
                                            <i class="ri ri-close-line text-muted"></i>
                                        @else
                                            <span class="badge bg-label-info">{{ $value }}</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
                <div>
                    <h5 class="mb-1">{{ __('subscription.transactions.title') }}</h5>
                    <p class="text-muted mb-0">{{ __('subscription.subtitle') }}</p>
                </div>
            </div>

            @if ($transactions->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="ri ri-bill-line icon-base mb-2"></i>
                    <p class="mb-0">{{ __('subscription.transactions.empty') }}</p>
                </div>
            @else
                @php
                    $statusBadges = [
                        'succeeded' => 'bg-label-success',
                        'pending' => 'bg-label-warning',
                        'waiting_for_capture' => 'bg-label-info',
                        'canceled' => 'bg-label-secondary',
                        'cancelled' => 'bg-label-secondary',
                        'failed' => 'bg-label-danger',
                    ];
                @endphp
                <div class="table-responsive">
                    <table class="table table-hover align-middle subscription-comparison-table">
                        <thead>
                            <tr>
                                <th>{{ __('subscription.transactions.date') }}</th>
                                <th>{{ __('subscription.transactions.plan') }}</th>
                                <th>{{ __('subscription.transactions.amount') }}</th>
                                <th>{{ __('subscription.transactions.status') }}</th>
                                <th>{{ __('subscription.transactions.payment_id') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transactions as $transaction)
                                @php
                                    $statusKey = strtolower($transaction->status ?? 'unknown');
                                    $statusClass = $statusBadges[$statusKey] ?? 'bg-label-secondary';
                                    $statusLabel = __('subscription.statuses.' . $statusKey, [], app()->getLocale());
                                    $transactionPlanDetails = $transaction->plan ? $planDetails->get($transaction->plan->slug, []) : [];
                                @endphp
                                <tr>
                                    <td class="text-nowrap">
                                        {{ $transaction->created_at->locale(app()->getLocale())->isoFormat('D MMM YYYY, HH:mm') }}
                                    </td>
                                    <td>{{ \Illuminate\Support\Arr::get($transactionPlanDetails, 'name', optional($transaction->plan)->name) }}</td>
                                    <td>
                                        {{ $currencySymbol }}{{ number_format((float) $transaction->amount, 2, '.', ' ') }}
                                    </td>
                                    <td>
                                        <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                    </td>
                                    <td class="text-break"><code>{{ $transaction->payment_id ?? '—' }}</code></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection

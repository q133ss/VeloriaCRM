<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PrepaymentRule;
use App\Models\Service;
use App\Models\Setting;
use App\Services\Integrations\IntegrationCatalog;
use App\Services\Prepayment\PrepaymentPolicyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The master's prepayment rules: the general rule (settings.deposit_policy)
 * and the date/weekday rules. Services and clients carry their own setting.
 */
class PrepaymentSettingsController extends Controller
{
    private const DEFAULTS = [
        'enabled' => false,
        'default_mode' => 'percent',
        'default_value' => 30,
        'new_clients_only' => false,
        'hold_minutes' => PrepaymentPolicyService::DEFAULT_HOLD_MINUTES,
        'no_show_threshold' => 0,
        'refund' => ['full_before_hours' => null, 'partial_percent' => 0],
    ];

    public function show(): JsonResponse
    {
        return response()->json(['data' => $this->payload($this->settings())]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'default_mode' => ['required', Rule::in(['fixed', 'percent'])],
            'default_value' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'new_clients_only' => ['required', 'boolean'],
            'hold_minutes' => ['required', 'integer', 'min:5', 'max:120'],
            'no_show_threshold' => ['required', 'integer', 'min:0', 'max:10'],
            'refund.full_before_hours' => ['nullable', 'integer', 'min:0', 'max:720'],
            'refund.partial_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        if ($data['default_mode'] === 'percent' && (float) $data['default_value'] > 100) {
            throw ValidationException::withMessages(['default_value' => __('prepayment.validation.percent_max')]);
        }

        $settings = $this->settings();

        // Switching it on before the shop is checked would only block slots nobody can pay for.
        if ($data['enabled'] && ! IntegrationCatalog::isVerified($settings, 'yookassa')) {
            throw ValidationException::withMessages(['enabled' => __('prepayment.validation.shop_not_verified')]);
        }

        $settings->forceFill(['deposit_policy' => [
            'enabled' => (bool) $data['enabled'],
            'default_mode' => $data['default_mode'],
            'default_value' => (float) $data['default_value'],
            'new_clients_only' => (bool) $data['new_clients_only'],
            'hold_minutes' => (int) $data['hold_minutes'],
            'no_show_threshold' => (int) $data['no_show_threshold'],
            'refund' => [
                'full_before_hours' => $data['refund']['full_before_hours'] ?? null,
                'partial_percent' => (int) ($data['refund']['partial_percent'] ?? 0),
            ],
        ]])->save();

        return response()->json([
            'data' => $this->payload($settings),
            'message' => __('prepayment.messages.saved'),
        ]);
    }

    public function storeRule(Request $request): JsonResponse
    {
        $rule = PrepaymentRule::query()->create(['user_id' => $this->userId()] + $this->validatedRule($request));

        return response()->json(['data' => $this->transformRule($rule), 'message' => __('prepayment.messages.rule_saved')], 201);
    }

    public function updateRule(Request $request, PrepaymentRule $rule): JsonResponse
    {
        $this->ensureOwned($rule);
        $rule->update($this->validatedRule($request));

        return response()->json(['data' => $this->transformRule($rule->refresh()), 'message' => __('prepayment.messages.rule_saved')]);
    }

    public function destroyRule(PrepaymentRule $rule): JsonResponse
    {
        $this->ensureOwned($rule);
        $rule->delete();

        return response()->json(['message' => __('prepayment.messages.rule_deleted')]);
    }

    private function validatedRule(Request $request): array
    {
        $userId = $this->userId();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'type' => ['required', Rule::in([PrepaymentRule::TYPE_PERIOD, PrepaymentRule::TYPE_WEEKDAY])],
            'starts_on' => ['required_if:type,period', 'nullable', 'date_format:Y-m-d'],
            'ends_on' => ['required_if:type,period', 'nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'weekdays' => ['required_if:type,weekday', 'nullable', 'array', 'min:1', 'max:7'],
            'weekdays.*' => ['integer', 'between:0,6'],
            'mode' => ['required', Rule::in(['fixed', 'percent'])],
            'value' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'service_ids' => ['nullable', 'array', 'max:100'],
            'service_ids.*' => ['integer', Rule::exists('services', 'id')->where('user_id', $userId)],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($data['mode'] === 'percent' && (float) $data['value'] > 100) {
            throw ValidationException::withMessages(['value' => __('prepayment.validation.percent_max')]);
        }

        $period = $data['type'] === PrepaymentRule::TYPE_PERIOD;

        return [
            'name' => trim($data['name']),
            'type' => $data['type'],
            'starts_on' => $period ? $data['starts_on'] : null,
            'ends_on' => $period ? $data['ends_on'] : null,
            'weekdays' => $period ? null : array_values(array_unique(array_map('intval', $data['weekdays']))),
            'mode' => $data['mode'],
            'value' => (float) $data['value'],
            'service_ids' => ! empty($data['service_ids']) ? array_values(array_unique(array_map('intval', $data['service_ids']))) : null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];
    }

    private function payload(Setting $settings): array
    {
        $policy = array_replace_recursive(self::DEFAULTS, (array) $settings->deposit_policy);
        $userId = $this->userId();

        $shop = match (true) {
            IntegrationCatalog::isVerified($settings, 'yookassa') => 'verified',
            IntegrationCatalog::isFilled($settings, 'yookassa') => 'unchecked',
            default => 'missing',
        };

        return [
            'policy' => $policy,
            'shop' => $shop,
            'webhook_url' => url('/api/v1/payments/yookassa/webhook'),
            'rules' => PrepaymentRule::query()->where('user_id', $userId)->orderByDesc('is_active')->orderBy('id')->get()
                ->map(fn (PrepaymentRule $rule) => $this->transformRule($rule))->all(),
            'services' => Service::query()->where('user_id', $userId)->orderBy('name')->get(['id', 'name'])
                ->map(fn (Service $service) => ['id' => $service->id, 'name' => $service->name])->all(),
        ];
    }

    private function transformRule(PrepaymentRule $rule): array
    {
        return [
            'id' => $rule->id,
            'name' => $rule->name,
            'type' => $rule->type,
            'starts_on' => $rule->starts_on?->toDateString(),
            'ends_on' => $rule->ends_on?->toDateString(),
            'weekdays' => $rule->weekdays,
            'mode' => $rule->mode,
            'value' => $rule->value,
            'service_ids' => $rule->service_ids ?? [],
            'is_active' => $rule->is_active,
        ];
    }

    private function settings(): Setting
    {
        return Setting::firstOrCreate(['user_id' => $this->userId()]);
    }

    private function ensureOwned(PrepaymentRule $rule): void
    {
        abort_unless((int) $rule->user_id === $this->userId(), 404);
    }

    private function userId(): int
    {
        $id = Auth::guard('sanctum')->id();
        abort_unless($id, 403);

        return (int) $id;
    }
}

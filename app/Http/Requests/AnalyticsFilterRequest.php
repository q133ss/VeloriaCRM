<?php

namespace App\Http\Requests;

use Illuminate\Support\Carbon;

class AnalyticsFilterRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'compare_to' => ['nullable', 'in:previous_period,previous_year,none'],
            'group_by' => ['nullable', 'in:day,week,month'],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        $start = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : now()->subDays(29)->startOfDay();

        $end = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->endOfDay()
            : now()->endOfDay();

        if ($end->lessThan($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        $validated['start_date'] = $start;
        $validated['end_date'] = $end;
        $validated['compare_to'] = $validated['compare_to'] ?? 'previous_period';
        $validated['group_by'] = $validated['group_by'] ?? $this->resolveDefaultGrouping($start, $end);

        return $validated;
    }

    protected function resolveDefaultGrouping(Carbon $start, Carbon $end): string
    {
        $days = $start->diffInDays($end) + 1;

        if ($days <= 31) {
            return 'day';
        }

        if ($days <= 90) {
            return 'week';
        }

        return 'month';
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Service;
use App\Models\Setting;
use App\Services\ScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Serves the first-run wizard on the dashboard.
 *
 * The wizard needs to save a working schedule without asking the master for a
 * name, an email, a timezone and a time format first, which is what the full
 * settings endpoint requires. So the schedule step gets its own narrow endpoint
 * that touches nothing but the schedule. Services and clients already have
 * lightweight create endpoints and are reused as they are.
 */
class OnboardingController extends Controller
{
    private const DAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    public function __construct(private readonly ScheduleService $scheduleService)
    {
    }

    public function status(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->buildStatus($request)]);
    }

    public function storeSchedule(Request $request): JsonResponse
    {
        $data = $request->validate([
            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['required', 'string', Rule::in(self::DAYS)],
            'start' => ['required', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'end' => ['required', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'step' => ['nullable', 'integer', Rule::in([30, 60, 90, 120])],
        ], [
            'days.required' => 'Отметьте хотя бы один рабочий день.',
            'days.min' => 'Отметьте хотя бы один рабочий день.',
            'start.required' => 'Укажите, во сколько начинается рабочий день.',
            'end.required' => 'Укажите, во сколько заканчивается рабочий день.',
        ]);

        $user = $request->user();
        $step = (int) ($data['step'] ?? 60);
        $slots = $this->buildSlots($data['start'], $data['end'], $step);

        if ($slots === []) {
            return response()->json([
                'message' => 'Между началом и концом дня не помещается ни одной записи. Проверьте время.',
                'errors' => ['end' => ['Конец дня должен быть позже начала.']],
            ], 422);
        }

        $workDays = array_values(array_unique($data['days']));
        $workHours = [];

        foreach ($workDays as $day) {
            $workHours[$day] = $slots;
        }

        $rules = $this->scheduleService->normalizeRules(null, $workDays, $workHours);
        $legacy = $this->scheduleService->deriveLegacyFields($rules);

        Setting::updateOrCreate(['user_id' => $user->id], [
            'schedule_rules' => $rules,
            'work_days' => $legacy['work_days'],
            'work_hours' => $legacy['work_hours'],
        ]);

        return response()->json([
            'message' => 'График сохранён.',
            'data' => $this->buildStatus($request),
        ]);
    }

    /**
     * Turn "from 10:00 to 20:00, an hour per client" into the discrete start
     * times the scheduler stores. The closing time is a boundary, not a slot:
     * a visit starting at 20:00 would end after the day is over.
     */
    private function buildSlots(string $start, string $end, int $step): array
    {
        $startMinutes = $this->toMinutes($start);
        $endMinutes = $this->toMinutes($end);

        if ($startMinutes === null || $endMinutes === null || $endMinutes <= $startMinutes) {
            return [];
        }

        $slots = [];

        for ($minute = $startMinutes; $minute + $step <= $endMinutes; $minute += $step) {
            $slots[] = sprintf('%02d:%02d', intdiv($minute, 60), $minute % 60);
        }

        return $slots;
    }

    private function toMinutes(string $time): ?int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        if ($hours < 0 || $hours > 23 || $minutes < 0 || $minutes > 59) {
            return null;
        }

        return $hours * 60 + $minutes;
    }

    private function buildStatus(Request $request): array
    {
        $user = $request->user();
        $setting = Setting::where('user_id', $user->id)->first();
        $serviceCount = Service::where('user_id', $user->id)->count();
        $clientCount = Client::where('user_id', $user->id)->count();
        $scheduleConfigured = $this->hasConfiguredSchedule($setting);

        return [
            'schedule_configured' => $scheduleConfigured,
            'service_count' => $serviceCount,
            'client_count' => $clientCount,
            'completed_steps' => collect([$scheduleConfigured, $serviceCount > 0, $clientCount > 0])->filter()->count(),
            'total_steps' => 3,
        ];
    }

    private function hasConfiguredSchedule(?Setting $setting): bool
    {
        if (! $setting) {
            return false;
        }

        $payload = $this->scheduleService->buildSettingsPayload($setting);
        $rules = $payload['schedule_rules'] ?? [];

        $hasWeeklySlots = collect($rules['weekly'] ?? [])->contains(
            fn ($dayRules) => ! empty($dayRules['enabled']) && ! empty($dayRules['slots'])
        );

        return $hasWeeklySlots
            || ! empty($rules['cycle']['slots'] ?? [])
            || ! empty($rules['monthly']['dates'] ?? []);
    }
}

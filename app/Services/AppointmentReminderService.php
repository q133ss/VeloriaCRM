<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * A client-facing "your appointment is tomorrow" reminder — same due-window
 * shape as AllergyReminderService, but always on for every master/plan tier
 * (Phase 10's gating table has no row for push notifications: they're a base
 * client-app experience, not something a master configures).
 */
class AppointmentReminderService
{
    public const REMINDER_HOURS_BEFORE = 24;

    private const UNSPECIFIED_SERVICE_LABEL = 'Услуга уточняется';

    public function __construct(private readonly ClientNotificationService $clientNotifications)
    {
    }

    public function dispatchDueReminders(?Carbon $now = null): array
    {
        $now = ($now ?: Carbon::now())->copy();
        // Hourly cron, one-hour-wide window: as `$now` advances by an hour each
        // run, every appointment's `starts_at` falls into exactly one window.
        $windowStart = $now->copy()->addHours(self::REMINDER_HOURS_BEFORE);
        $windowEnd = $windowStart->copy()->addHour();

        $appointments = Appointment::query()
            ->with('client')
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->whereNull('reminder_notified_at')
            ->whereBetween('starts_at', [$windowStart, $windowEnd])
            ->get();

        $timezonesByMasterId = User::query()
            ->whereIn('id', $appointments->pluck('user_id')->unique())
            ->pluck('timezone', 'id');

        $result = [
            'processed' => 0,
            'sent' => 0,
            'skipped' => 0,
            'items' => [],
        ];

        foreach ($appointments as $appointment) {
            $result['processed']++;

            $client = $appointment->client;

            if (! $client) {
                $result['skipped']++;
                $result['items'][] = ['appointment_id' => $appointment->id, 'status' => 'skipped', 'reason' => 'client_missing'];
                continue;
            }

            $timezone = $timezonesByMasterId->get($appointment->user_id) ?: config('app.timezone');
            $serviceLabel = (string) Arr::get($appointment->meta, 'service_label', self::UNSPECIFIED_SERVICE_LABEL);

            $this->clientNotifications->notifyAppointmentReminder(
                $client,
                $serviceLabel,
                $appointment->starts_at->copy()->timezone($timezone),
            );

            $appointment->forceFill(['reminder_notified_at' => $now])->save();

            $result['sent']++;
            $result['items'][] = ['appointment_id' => $appointment->id, 'status' => 'sent'];
        }

        return $result;
    }
}

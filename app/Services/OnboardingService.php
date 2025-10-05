<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\User;

class OnboardingService
{
    /**
     * Build onboarding steps and progress metadata for the given user.
     */
    public function getProgressForUser(User $user, array $context = []): array
    {
        $request = request();
        $cacheKey = 'onboarding.progress.' . $user->getKey();

        if ($request && $request->attributes->has($cacheKey)) {
            return $request->attributes->get($cacheKey);
        }

        $setting = $context['setting'] ?? null;
        if (! $setting instanceof Setting) {
            $setting = Setting::where('user_id', $user->getKey())->first();
        }

        $catalogCategoriesCount = $context['catalog_categories_count'] ?? null;
        if ($catalogCategoriesCount === null) {
            $catalogCategoriesCount = ServiceCategory::where('user_id', $user->getKey())->count();
        }

        $catalogServicesCount = $context['catalog_services_count'] ?? null;
        if ($catalogServicesCount === null) {
            $catalogServicesCount = Service::where('user_id', $user->getKey())->count();
        }

        $clientsCount = $context['clients_count'] ?? null;
        if ($clientsCount === null) {
            $clientsCount = Client::where('user_id', $user->getKey())->count();
        }

        $hasAnyAppointments = $context['has_appointments'] ?? null;
        if ($hasAnyAppointments === null) {
            $hasAnyAppointments = Appointment::where('user_id', $user->getKey())->exists();
        }

        $hasSmtpSettings = $setting && filled($setting->smtp_host) && filled($setting->smtp_username) && filled($setting->smtp_from_address ?? null);
        $hasWhatsappSettings = $setting && filled($setting->whatsapp_api_key) && filled($setting->whatsapp_sender ?? null);
        $hasTelegramSettings = $setting && filled($setting->telegram_bot_token) && filled($setting->telegram_sender ?? null);
        $hasSmsSettings = $setting && filled($setting->smsaero_email) && filled($setting->smsaero_api_key);

        $steps = [
            [
                'key' => 'catalog',
                'title' => __('dashboard.onboarding.steps.catalog.title'),
                'description' => __('dashboard.onboarding.steps.catalog.description'),
                'action' => route('services.index'),
                'action_label' => __('dashboard.onboarding.steps.catalog.action'),
                'completed' => $catalogCategoriesCount > 0 && $catalogServicesCount > 0,
            ],
            [
                'key' => 'clients',
                'title' => __('dashboard.onboarding.steps.clients.title'),
                'description' => __('dashboard.onboarding.steps.clients.description'),
                'action' => route('clients.create'),
                'action_label' => __('dashboard.onboarding.steps.clients.action'),
                'completed' => $clientsCount > 0,
            ],
            [
                'key' => 'appointments',
                'title' => __('dashboard.onboarding.steps.appointments.title'),
                'description' => __('dashboard.onboarding.steps.appointments.description'),
                'action' => route('orders.create'),
                'action_label' => __('dashboard.onboarding.steps.appointments.action'),
                'completed' => (bool) $hasAnyAppointments,
            ],
            [
                'key' => 'settings',
                'title' => __('dashboard.onboarding.steps.settings.title'),
                'description' => __('dashboard.onboarding.steps.settings.description'),
                'action' => route('settings'),
                'action_label' => __('dashboard.onboarding.steps.settings.action'),
                'completed' => $hasSmtpSettings && $hasWhatsappSettings && $hasTelegramSettings && $hasSmsSettings,
            ],
        ];

        $statusLabels = [
            'done' => __('dashboard.onboarding.status.done'),
            'next' => __('dashboard.onboarding.status.next'),
        ];

        $progressSignature = collect($steps)
            ->map(fn (array $step) => $step['key'] . ':' . ($step['completed'] ? '1' : '0'))
            ->implode('|');

        $data = [
            'show' => collect($steps)->contains(fn (array $step) => ! $step['completed']),
            'steps' => $steps,
            'status_labels' => $statusLabels,
            'hint' => __('dashboard.onboarding.settings_hint'),
            'hash' => sha1($progressSignature),
            'next_step' => collect($steps)->firstWhere('completed', false),
        ];

        if ($request) {
            $request->attributes->set($cacheKey, $data);
        }

        return $data;
    }
}

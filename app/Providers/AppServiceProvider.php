<?php

namespace App\Providers;

use App\Services\OnboardingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            $user = Auth::user();

            if (! $user) {
                return;
            }

            /** @var OnboardingService $onboardingService */
            $onboardingService = app(OnboardingService::class);

            $onboarding = $onboardingService->getProgressForUser($user);

            $view->with('layoutOnboarding', array_merge($onboarding, [
                'user_id' => $user->getKey(),
            ]));
        });
    }
}

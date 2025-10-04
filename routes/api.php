<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\V1\CalendarController;
use App\Http\Controllers\Api\V1\ClientController as ApiClientController;
use App\Http\Controllers\Api\V1\OrderController as ApiOrderController;
use App\Http\Controllers\Api\V1\ServiceCategoryController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\HelpCenterController;
use App\Http\Controllers\Api\V1\Marketing\MarketingCampaignController;
use App\Http\Controllers\Api\V1\Marketing\PromotionController;
use App\Http\Controllers\Api\V1\Marketing\WarmupController;
use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\LandingController;
use App\Http\Controllers\Api\V1\SupportTicketController;
use App\Http\Controllers\Api\V1\Learning\KnowledgeController as LearningKnowledgeController;
use App\Http\Controllers\Api\V1\Learning\LearningPlanController;
use App\Http\Controllers\Api\V1\Learning\LessonController as LearningLessonController;
use App\Http\Controllers\Api\V1\SubscriptionController as ApiSubscriptionController;

Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('api.register');
    Route::post('/login', [AuthController::class, 'login'])->name('api.login');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum')->name('api.logout');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('api.forgot');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('api.reset');
    Route::middleware('auth:sanctum')->get('/auth/me', [AuthController::class, 'me'])->name('api.me');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/settings', [SettingController::class, 'index']);
        Route::patch('/settings', [SettingController::class, 'update']);
        Route::get('/analytics/overview', [AnalyticsController::class, 'overview']);
        Route::delete('/user', [UserController::class, 'destroy']);
        Route::get('/clients/options', [ApiClientController::class, 'options']);
        Route::get('/clients', [ApiClientController::class, 'index']);
        Route::post('/clients', [ApiClientController::class, 'store']);
        Route::get('/clients/{client}', [ApiClientController::class, 'show']);
        Route::get('/clients/{client}/analytics', [ApiClientController::class, 'analytics']);
        Route::get('/clients/{client}/recommendations', [ApiClientController::class, 'recommendations']);
        Route::patch('/clients/{client}', [ApiClientController::class, 'update']);
        Route::delete('/clients/{client}', [ApiClientController::class, 'destroy']);
        Route::post('/clients/{client}/reminders', [ApiClientController::class, 'sendReminder']);
        Route::get('/orders/options', [ApiOrderController::class, 'options']);
        Route::get('/orders', [ApiOrderController::class, 'index']);
        Route::post('/orders', [ApiOrderController::class, 'store']);
        Route::get('/orders/{order}', [ApiOrderController::class, 'show']);
        Route::patch('/orders/{order}', [ApiOrderController::class, 'update']);
        Route::delete('/orders/{order}', [ApiOrderController::class, 'destroy']);
        Route::post('/orders/bulk', [ApiOrderController::class, 'bulk']);
        Route::post('/orders/quick-create', [ApiOrderController::class, 'quickStore']);
        Route::post('/orders/{order}/complete', [ApiOrderController::class, 'complete']);
        Route::post('/orders/{order}/start', [ApiOrderController::class, 'start']);
        Route::post('/orders/{order}/remind', [ApiOrderController::class, 'remind']);
        Route::post('/orders/{order}/cancel', [ApiOrderController::class, 'cancel']);
        Route::post('/orders/{order}/reschedule', [ApiOrderController::class, 'reschedule']);
        Route::get('/orders/{order}/analytics', [ApiOrderController::class, 'analytics']);
        Route::get('/calendar/events', [CalendarController::class, 'events']);
        Route::get('/calendar/day', [CalendarController::class, 'day']);
        Route::get('/help/overview', [HelpCenterController::class, 'overview']);
        Route::get('/help/tickets', [SupportTicketController::class, 'index']);
        Route::post('/help/tickets', [SupportTicketController::class, 'store']);
        Route::get('/help/tickets/{ticket}', [SupportTicketController::class, 'show']);
        Route::post('/help/tickets/{ticket}/messages', [SupportTicketController::class, 'reply']);
        Route::get('/services/options', [ServiceController::class, 'options']);
        Route::get('/services', [ServiceController::class, 'index']);
        Route::post('/services', [ServiceController::class, 'store']);
        Route::get('/services/{service}', [ServiceController::class, 'show']);
        Route::patch('/services/{service}', [ServiceController::class, 'update']);
        Route::delete('/services/{service}', [ServiceController::class, 'destroy']);
        Route::get('/landings/options', [LandingController::class, 'options']);
        Route::apiResource('landings', LandingController::class);
        Route::prefix('marketing')->group(function () {
            Route::get('/campaigns', [MarketingCampaignController::class, 'index']);
            Route::post('/campaigns', [MarketingCampaignController::class, 'store']);
            Route::get('/campaigns/options', [MarketingCampaignController::class, 'options']);
            Route::get('/campaigns/{campaign}', [MarketingCampaignController::class, 'show']);
            Route::patch('/campaigns/{campaign}', [MarketingCampaignController::class, 'update']);
            Route::delete('/campaigns/{campaign}', [MarketingCampaignController::class, 'destroy']);
            Route::post('/campaigns/{campaign}/launch', [MarketingCampaignController::class, 'launch']);
            Route::post('/campaigns/{campaign}/winner', [MarketingCampaignController::class, 'selectWinner']);

            Route::get('/promotions', [PromotionController::class, 'index']);
            Route::post('/promotions', [PromotionController::class, 'store']);
            Route::get('/promotions/options', [PromotionController::class, 'options']);
            Route::get('/promotions/{promotion}', [PromotionController::class, 'show']);
            Route::patch('/promotions/{promotion}', [PromotionController::class, 'update']);
            Route::post('/promotions/{promotion}/archive', [PromotionController::class, 'archive']);
            Route::post('/promotions/{promotion}/usage', [PromotionController::class, 'recordUsage']);

            Route::get('/warmup', [WarmupController::class, 'index']);
        });
        Route::get('/service-categories', [ServiceCategoryController::class, 'index']);
        Route::post('/service-categories', [ServiceCategoryController::class, 'store']);
        Route::patch('/service-categories/{category}', [ServiceCategoryController::class, 'update']);
        Route::delete('/service-categories/{category}', [ServiceCategoryController::class, 'destroy']);

        Route::prefix('learning')->group(function () {
            Route::get('/plan', [LearningPlanController::class, 'show']);
            Route::patch('/tasks/{task}', [LearningPlanController::class, 'updateTask']);
            Route::get('/lessons', [LearningLessonController::class, 'index']);
            Route::get('/knowledge', [LearningKnowledgeController::class, 'index']);
        });

        Route::get('/subscription', [ApiSubscriptionController::class, 'show']);
        Route::post('/subscription/upgrade', [ApiSubscriptionController::class, 'upgrade']);
        Route::post('/subscription/cancel', [ApiSubscriptionController::class, 'cancel']);
    });
});

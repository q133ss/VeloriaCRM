<?php

namespace App\OpenApi\OperationTransformers;

use Dedoc\Scramble\Contracts\OperationTransformer;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Support\Str;

class ApplyVeloriaOperationMetadata implements OperationTransformer
{
    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        if (trim($operation->summary) === '') {
            $operation->summary($this->makeSummary($routeInfo));
        }

        if (trim($operation->description) !== '') {
            return;
        }

        $uri = $routeInfo->route->uri;

        if (Str::contains($uri, 'trends/overview')) {
            $operation->description('Legacy compatibility alias of the canonical useful overview endpoint.');
            return;
        }

        $controller = class_basename((string) $routeInfo->className());
        $resource = $this->resourceNames($controller);

        $operation->description('Veloria CRM API endpoint for '.$resource['plural'].'.');
    }

    private function makeSummary(RouteInfo $routeInfo): string
    {
        $controller = class_basename((string) $routeInfo->className());
        $method = (string) $routeInfo->methodName();
        $resource = $this->resourceNames($controller);
        $middlewares = $routeInfo->route->gatherMiddleware();

        return match ($method) {
            'index' => 'List '.$resource['plural'],
            'show' => 'Get '.$resource['singular'],
            'store' => 'Create '.$resource['singular'],
            'update' => 'Update '.$resource['singular'],
            'destroy' => 'Delete '.$resource['singular'],
            'options' => 'Get '.$resource['plural'].' options',
            'overview' => 'Get '.$resource['singular'].' overview',
            'analytics' => 'Get '.$resource['singular'].' analytics',
            'recommendations' => 'Get '.$resource['singular'].' recommendations',
            'bulk' => 'Bulk update '.$resource['plural'],
            'quickStore' => 'Quick create '.$resource['singular'],
            'complete' => 'Complete '.$resource['singular'],
            'start' => 'Start '.$resource['singular'],
            'remind' => 'Mark '.$resource['singular'].' as reminded',
            'cancel' => 'Cancel '.$resource['singular'],
            'reschedule' => 'Reschedule '.$resource['singular'],
            'launch' => 'Launch '.$resource['singular'],
            'selectWinner' => 'Select '.$resource['singular'].' winner',
            'recordUsage' => 'Record '.$resource['singular'].' usage',
            'archive' => 'Archive '.$resource['singular'],
            'reply' => 'Reply to '.$resource['singular'],
            'upgrade' => 'Upgrade subscription',
            'register' => 'Register user',
            'login' => $this->hasMiddleware($middlewares, 'token.client') || Str::contains((string) $routeInfo->className(), 'Client\\')
                ? 'Login client portal'
                : 'Login user',
            'verifyLogin' => 'Verify client portal login',
            'logout' => 'Logout current session',
            'me' => $this->hasMiddleware($middlewares, 'token.client') ? 'Get current client' : 'Get current user',
            'sendResetLink' => 'Send password reset link',
            'resetPassword' => 'Reset password',
            'sendTestDigest' => 'Send test useful digest',
            'updatePreferences' => 'Update useful digest preferences',
            default => Str::headline($method).' '.$resource['plural'],
        };
    }

    /**
     * @return array{singular: string, plural: string}
     */
    private function resourceNames(string $controller): array
    {
        return match ($controller) {
            'AuthController' => ['singular' => 'user account', 'plural' => 'user accounts'],
            'AnalyticsController' => ['singular' => 'analytics overview', 'plural' => 'analytics data'],
            'CalendarController' => ['singular' => 'calendar view', 'plural' => 'calendar views'],
            'ClientController' => ['singular' => 'client', 'plural' => 'clients'],
            'HelpCenterController' => ['singular' => 'help center overview', 'plural' => 'help center data'],
            'LandingController' => ['singular' => 'landing', 'plural' => 'landings'],
            'NotificationController' => ['singular' => 'notification', 'plural' => 'notifications'],
            'OrderController' => ['singular' => 'order', 'plural' => 'orders'],
            'ServiceCategoryController' => ['singular' => 'service category', 'plural' => 'service categories'],
            'ServiceController' => ['singular' => 'service', 'plural' => 'services'],
            'SettingController' => ['singular' => 'setting', 'plural' => 'settings'],
            'SubscriptionController' => ['singular' => 'subscription', 'plural' => 'subscription'],
            'SupportTicketController' => ['singular' => 'support ticket', 'plural' => 'support tickets'],
            'TrendsController' => ['singular' => 'trends overview', 'plural' => 'trends data'],
            'UsefulController' => ['singular' => 'useful digest', 'plural' => 'useful content'],
            'UserController' => ['singular' => 'user profile', 'plural' => 'user profiles'],
            'WaitlistController' => ['singular' => 'waitlist entry', 'plural' => 'waitlist entries'],
            'MarketingCampaignController' => ['singular' => 'marketing campaign', 'plural' => 'marketing campaigns'],
            'PromotionController' => ['singular' => 'promotion', 'plural' => 'promotions'],
            'WarmupController' => ['singular' => 'warmup overview', 'plural' => 'warmup data'],
            'BookingController' => ['singular' => 'booking', 'plural' => 'bookings'],
            'AdminOverviewController' => ['singular' => 'admin overview', 'plural' => 'admin overview'],
            'AdminAuditController' => ['singular' => 'admin audit log', 'plural' => 'admin audit logs'],
            'AdminUserController' => ['singular' => 'admin user', 'plural' => 'admin users'],
            'AdminSupportTicketController' => ['singular' => 'admin support ticket', 'plural' => 'admin support tickets'],
            'AdminUsefulPostController' => ['singular' => 'useful post', 'plural' => 'useful posts'],
            default => [
                'singular' => Str::of($controller)->beforeLast('Controller')->headline()->lower()->toString(),
                'plural' => Str::of($controller)->beforeLast('Controller')->headline()->lower()->append('s')->toString(),
            ],
        };
    }

    /**
     * @param  string[]  $middlewares
     */
    private function hasMiddleware(array $middlewares, string $needle): bool
    {
        return collect($middlewares)->contains(fn (string $middleware) => $middleware === $needle);
    }
}

<?php

namespace App\OpenApi\DocumentTransformers;

use Dedoc\Scramble\Contracts\DocumentTransformer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\Tag;

class ApplyVeloriaApiDocument implements DocumentTransformer
{
    public function handle(OpenApi $document, OpenApiContext $context): void
    {
        $document->components->addSecurityScheme(
            'bearerAuth',
            SecurityScheme::http('bearer', 'Sanctum token')
                ->as('bearerAuth')
                ->setDescription('Use the bearer token returned by /api/v1/login or /api/v1/client/login.')
        );

        $document->components->addSecurityScheme(
            'tokenCookie',
            SecurityScheme::apiKey('cookie', 'token')
                ->as('tokenCookie')
                ->setDescription('Browser alternative. The app reads the token cookie and forwards it as a bearer token.')
        );

        $tagMeta = [
            'Auth' => ['weight' => 10, 'description' => 'User authentication, registration, password reset, and current user profile.'],
            'Booking' => ['weight' => 20, 'description' => 'Client portal booking flow: services, slots, appointments, and waitlist.'],
            'Client' => ['weight' => 30, 'description' => 'Client directory, profiles, analytics, and AI-based recommendations.'],
            'Order' => ['weight' => 40, 'description' => 'Appointments and orders: creation, lifecycle actions, bulk actions, and analytics.'],
            'Calendar' => ['weight' => 50, 'description' => 'Calendar event and day views used by the scheduling UI.'],
            'Service' => ['weight' => 60, 'description' => 'Service catalog CRUD for master-owned services.'],
            'ServiceCategory' => ['weight' => 70, 'description' => 'Service category management and lookup data.'],
            'Waitlist' => ['weight' => 80, 'description' => 'Waitlist entries, matching, and follow-up scheduling helpers.'],
            'Analytics' => ['weight' => 90, 'description' => 'Business analytics overview and lazy-loaded insights.'],
            'Useful' => ['weight' => 100, 'description' => 'Canonical useful content overview, digest preferences, and test digest delivery.'],
            'Trends' => ['weight' => 110, 'description' => 'Legacy compatibility alias that mirrors the useful overview payload.'],
            'Subscription' => ['weight' => 120, 'description' => 'Plan details, upgrade flow, and cancellation endpoints.'],
            'Setting' => ['weight' => 130, 'description' => 'Workspace settings and integrations.'],
            'Notification' => ['weight' => 140, 'description' => 'Notifications feed and state update actions.'],
            'Landing' => ['weight' => 150, 'description' => 'Landing page CRUD and option payloads.'],
            'MarketingCampaign' => ['weight' => 160, 'description' => 'Marketing campaign CRUD, launch flow, and winner selection.'],
            'Promotion' => ['weight' => 170, 'description' => 'Promotions CRUD, archive flow, and usage tracking.'],
            'Warmup' => ['weight' => 180, 'description' => 'Marketing warmup overview payload.'],
            'HelpCenter' => ['weight' => 190, 'description' => 'Help center overview used by the support UI.'],
            'SupportTicket' => ['weight' => 200, 'description' => 'Support tickets and ticket message threads for regular users.'],
            'User' => ['weight' => 210, 'description' => 'User profile destructive actions and avatar management.'],
            'AdminOverview' => ['weight' => 220, 'description' => 'Admin dashboard overview metrics and summaries.'],
            'AdminAudit' => ['weight' => 230, 'description' => 'Admin audit log search and filtering.'],
            'AdminUser' => ['weight' => 240, 'description' => 'Admin user management and subscription overrides.'],
            'AdminSupportTicket' => ['weight' => 250, 'description' => 'Admin support queue operations and replies.'],
            'AdminUsefulPost' => ['weight' => 260, 'description' => 'Admin useful content CRUD for curated posts.'],
        ];

        $document->tags = collect($document->tags)
            ->map(function (Tag $tag) use ($tagMeta) {
                $meta = $tagMeta[$tag->name] ?? null;

                if ($meta) {
                    $tag->description ??= $meta['description'];
                    $tag->setAttribute('weight', $meta['weight']);
                }

                return $tag;
            })
            ->sortBy(fn (Tag $tag) => [$tag->getAttribute('weight', 999), $tag->name])
            ->values()
            ->all();
    }
}

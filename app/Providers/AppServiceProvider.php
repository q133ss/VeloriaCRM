<?php

namespace App\Providers;

use App\Models\Order;
use App\Observers\OrderObserver;
use App\OpenApi\DocumentTransformers\ApplyVeloriaApiDocument;
use App\OpenApi\OperationTransformers\ApplyVeloriaApiSecurity;
use App\OpenApi\OperationTransformers\ApplyVeloriaOperationMetadata;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\Landing\TemplateRegistry::class);
        // One instance per request: the landing page binds its landing and edit
        // mode once and the Blade components read them back.
        $this->app->scoped(\App\Services\Landing\LandingContent::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Order::observe(OrderObserver::class);

        if (! class_exists(\Dedoc\Scramble\Scramble::class)) {
            return;
        }

        \Dedoc\Scramble\Scramble::configure()
            ->withDocumentTransformers([
                ApplyVeloriaApiDocument::class,
            ])
            ->withOperationTransformers([
                ApplyVeloriaOperationMetadata::class,
                ApplyVeloriaApiSecurity::class,
            ]);
    }
}

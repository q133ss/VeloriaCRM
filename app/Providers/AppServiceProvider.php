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
        //
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

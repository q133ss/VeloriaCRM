<?php

namespace App\OpenApi\OperationTransformers;

use Dedoc\Scramble\Contracts\OperationTransformer;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Support\Str;

class ApplyVeloriaApiSecurity implements OperationTransformer
{
    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        if ($operation->security === []) {
            return;
        }

        $middlewares = $routeInfo->route->gatherMiddleware();

        if (! $this->hasMiddleware($middlewares, 'auth:sanctum')) {
            return;
        }

        $operation->addSecurity(new SecurityRequirement(['bearerAuth' => []]));
        $operation->addSecurity(new SecurityRequirement(['tokenCookie' => []]));

        $notes = [];

        if ($this->hasMiddleware($middlewares, 'token.client')) {
            $notes[] = 'Requires an authenticated client portal token.';
        } elseif ($this->hasMiddleware($middlewares, 'token.user')) {
            $notes[] = 'Requires an authenticated user token.';
        }

        if ($this->hasMiddleware($middlewares, 'user.active')) {
            $notes[] = 'The authenticated user account must be active.';
        }

        if ($this->hasMiddleware($middlewares, 'admin.access')) {
            $notes[] = 'Administrator access is required.';
        }

        if (! count($notes)) {
            return;
        }

        $description = trim($operation->description);
        $suffix = implode(' ', $notes);

        $operation->description($description !== '' ? $description."\n\n".$suffix : $suffix);
    }

    /**
     * @param  string[]  $middlewares
     */
    private function hasMiddleware(array $middlewares, string $needle): bool
    {
        return collect($middlewares)->contains(function (string $middleware) use ($needle) {
            return $middleware === $needle || Str::startsWith($middleware, $needle.':');
        });
    }
}

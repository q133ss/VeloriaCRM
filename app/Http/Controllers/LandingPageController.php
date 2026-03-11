<?php

namespace App\Http\Controllers;

use App\Models\Landing;
use App\Models\Service;
use Illuminate\Support\Collection;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    public function __invoke(Request $request, string $slug): View
    {
        $landing = Landing::query()->where('slug', $slug)->firstOrFail();

        $isPreview = $request->boolean('preview');

        if (! $landing->is_active && ! $isPreview) {
            abort(404);
        }

        if (! $isPreview) {
            Landing::whereKey($landing->id)->increment('views');
            $landing->refresh();
        }

        $template = $landing->landing ?: $this->defaultTemplateForType($landing->type);

        if (! view()->exists($template)) {
            $template = $this->defaultTemplateForType($landing->type);
        }

        return view('landings.public', [
            'landing' => $landing,
            'template' => $template,
            'isPreview' => $isPreview,
            'featuredServices' => $this->resolveFeaturedServices($landing),
        ]);
    }

    protected function defaultTemplateForType(string $type): string
    {
        return match ($type) {
            'promotion' => 'landings.templates.promotion',
            'service' => 'landings.templates.service',
            'seasonal' => 'landings.templates.seasonal',
            'consultation' => 'landings.templates.consultation',
            default => 'landings.templates.general',
        };
    }

    protected function resolveFeaturedServices(Landing $landing): Collection
    {
        $settings = $landing->settings ?? [];
        $query = Service::query()
            ->where('user_id', $landing->user_id)
            ->orderBy('name');

        if ($landing->type === 'general' && data_get($settings, 'show_all_services')) {
            return $query->get(['id', 'name', 'base_price', 'duration_min']);
        }

        $serviceIds = collect(data_get($settings, 'service_ids', []))
            ->merge(array_filter([data_get($settings, 'service_id')]))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($serviceIds->isNotEmpty()) {
            return $query
                ->whereIn('id', $serviceIds->all())
                ->get(['id', 'name', 'base_price', 'duration_min']);
        }

        return $query->limit(5)->get(['id', 'name', 'base_price', 'duration_min']);
    }
}

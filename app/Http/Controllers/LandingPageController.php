<?php

namespace App\Http\Controllers;

use App\Models\Landing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    public function __invoke(Request $request, string $slug): View
    {
        $landing = Landing::where('slug', $slug)->firstOrFail();

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
        ]);
    }

    protected function defaultTemplateForType(string $type): string
    {
        return match ($type) {
            'promotion' => 'landings.templates.promotion',
            'service' => 'landings.templates.service',
            default => 'landings.templates.general',
        };
    }
}

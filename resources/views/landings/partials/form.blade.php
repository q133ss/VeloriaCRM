<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="mb-1">{{ __('landings.form.title') }}</h4>
        <p class="text-muted mb-0">{{ __('landings.form.subtitle') }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('landings.index') }}" class="btn btn-outline-secondary">
            <i class="ri ri-arrow-left-line me-1"></i>
            {{ __('landings.actions.back_to_list') }}
        </a>
        <button type="button" class="btn btn-outline-primary d-none" id="landing-preview-btn">
            <i class="ri ri-eye-line me-1"></i>
            {{ __('landings.actions.preview') }}
        </button>
        <button type="submit" form="landing-form" class="btn btn-primary">
            <i class="ri ri-save-line me-1"></i>
            {{ __('landings.actions.save') }}
        </button>
    </div>
</div>

<div id="landing-alerts"></div>

<form id="landing-form" class="needs-validation" novalidate>
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="landing-title" class="form-label">{{ __('landings.form.fields.title') }}</label>
                    <input type="text" class="form-control" id="landing-title" name="title" required />
                    <div class="invalid-feedback">{{ __('landings.validation.title_required') }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label d-flex align-items-center justify-content-between">
                        <span>{{ __('landings.form.fields.status') }}</span>
                        <span class="badge bg-label-success" id="landing-status-indicator">{{ __('landings.statuses.active') }}</span>
                    </label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="landing-active" name="is_active" checked />
                        <label class="form-check-label" for="landing-active">{{ __('landings.form.fields.is_active_label') }}</label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('landings.form.fields.type') }}</label>
                    <div class="d-flex flex-wrap gap-3" id="landing-type-options">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="type" id="landing-type-general" value="general" checked />
                            <label class="form-check-label" for="landing-type-general">🏠 {{ __('landings.types.general') }}</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="type" id="landing-type-promotion" value="promotion" />
                            <label class="form-check-label" for="landing-type-promotion">🎯 {{ __('landings.types.promotion') }}</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="type" id="landing-type-service" value="service" />
                            <label class="form-check-label" for="landing-type-service">✨ {{ __('landings.types.service') }}</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="landing-slug" class="form-label">{{ __('landings.form.fields.slug') }}</label>
                    <div class="input-group">
                        <span class="input-group-text" id="landing-url-prefix">{{ config('app.url') }}/l/</span>
                        <input type="text" class="form-control" id="landing-slug" name="slug" placeholder="{{ __('landings.form.placeholders.slug') }}" />
                    </div>
                    <div class="form-text" id="landing-full-url"></div>
                </div>
                <div class="col-md-6">
                    <label for="landing-template" class="form-label">{{ __('landings.form.fields.template') }}</label>
                    <input type="text" class="form-control" id="landing-template" name="landing" placeholder="landings.templates.general" />
                    <div class="form-text">{{ __('landings.form.helpers.template') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header pb-0">
            <h5 class="card-title mb-0">{{ __('landings.form.sections.base_settings') }}</h5>
            <p class="text-muted small mb-0">{{ __('landings.form.sections.base_settings_hint') }}</p>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="landing-primary-color" class="form-label">{{ __('landings.form.fields.primary_color') }}</label>
                    <select class="form-select" id="landing-primary-color" name="settings[primary_color]">
                        <option value="indigo">{{ __('landings.colors.indigo') }}</option>
                        <option value="emerald">{{ __('landings.colors.emerald') }}</option>
                        <option value="sunset">{{ __('landings.colors.sunset') }}</option>
                        <option value="midnight">{{ __('landings.colors.midnight') }}</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="landing-background-type" class="form-label">{{ __('landings.form.fields.background_type') }}</label>
                    <select class="form-select" id="landing-background-type" name="settings[background_type]">
                        <option value="preset">{{ __('landings.form.options.background_type.preset') }}</option>
                        <option value="upload">{{ __('landings.form.options.background_type.upload') }}</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="landing-background-value" class="form-label">{{ __('landings.form.fields.background_value') }}</label>
                    <input type="text" class="form-control" id="landing-background-value" name="settings[background_value]" placeholder="{{ __('landings.form.placeholders.background_value') }}" />
                    <div class="form-text">{{ __('landings.form.helpers.background_value') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4" data-type-section="general">
        <div class="card-header pb-0">
            <h5 class="card-title mb-0">{{ __('landings.form.sections.general_title') }}</h5>
            <p class="text-muted small mb-0">{{ __('landings.form.sections.general_hint') }}</p>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="landing-greeting" class="form-label">{{ __('landings.form.fields.greeting') }}</label>
                <textarea class="form-control" id="landing-greeting" name="settings[greeting]" rows="3"></textarea>
            </div>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="landing-all-services" name="settings[show_all_services]" />
                <label class="form-check-label" for="landing-all-services">{{ __('landings.form.fields.show_all_services') }}</label>
            </div>
            <div>
                <label for="landing-service-ids" class="form-label">{{ __('landings.form.fields.service_ids') }}</label>
                <select class="form-select" id="landing-service-ids" name="settings[service_ids][]" multiple></select>
                <div class="form-text">{{ __('landings.form.helpers.service_ids') }}</div>
            </div>
        </div>
    </div>

    <div class="card mb-4 d-none" data-type-section="promotion">
        <div class="card-header pb-0">
            <h5 class="card-title mb-0">{{ __('landings.form.sections.promotion_title') }}</h5>
            <p class="text-muted small mb-0">{{ __('landings.form.sections.promotion_hint') }}</p>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="landing-promotion" class="form-label">{{ __('landings.form.fields.promotion') }}</label>
                    <select class="form-select" id="landing-promotion" name="settings[promotion_id]">
                        <option value="">{{ __('landings.form.placeholders.promotion') }}</option>
                    </select>
                    <div class="form-text" id="landing-promotion-details"></div>
                </div>
                <div class="col-md-6">
                    <label for="landing-headline" class="form-label">{{ __('landings.form.fields.headline') }}</label>
                    <input type="text" class="form-control" id="landing-headline" name="settings[headline]" />
                </div>
                <div class="col-12">
                    <label for="landing-description" class="form-label">{{ __('landings.form.fields.description') }}</label>
                    <textarea class="form-control" id="landing-description" name="settings[description]" rows="3"></textarea>
                </div>
                <div class="col-md-4">
                    <label for="landing-discount" class="form-label">{{ __('landings.form.fields.discount_percent') }}</label>
                    <input type="number" class="form-control" id="landing-discount" name="settings[discount_percent]" min="0" max="100" step="0.1" />
                </div>
                <div class="col-md-4">
                    <label for="landing-promo-code" class="form-label">{{ __('landings.form.fields.promo_code') }}</label>
                    <input type="text" class="form-control" id="landing-promo-code" name="settings[promo_code]" />
                </div>
                <div class="col-md-4">
                    <label for="landing-ends-at" class="form-label">{{ __('landings.form.fields.ends_at') }}</label>
                    <input type="date" class="form-control" id="landing-ends-at" name="settings[ends_at]" />
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4 d-none" data-type-section="service">
        <div class="card-header pb-0">
            <h5 class="card-title mb-0">{{ __('landings.form.sections.service_title') }}</h5>
            <p class="text-muted small mb-0">{{ __('landings.form.sections.service_hint') }}</p>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="landing-service" class="form-label">{{ __('landings.form.fields.service') }}</label>
                    <select class="form-select" id="landing-service" name="settings[service_id]">
                        <option value="">{{ __('landings.form.placeholders.service') }}</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="landing-service-description" class="form-label">{{ __('landings.form.fields.service_description') }}</label>
                    <textarea class="form-control" id="landing-service-description" name="settings[service_description]" rows="3"></textarea>
                </div>
            </div>
        </div>
    </div>
</form>

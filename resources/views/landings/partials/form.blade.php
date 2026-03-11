<style>
    .landing-scenario-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
    }

    .landing-scenario-card {
        position: relative;
        display: block;
        border: 1px solid rgba(var(--bs-body-color-rgb), 0.12);
        border-radius: 1.25rem;
        background: rgba(var(--bs-body-bg-rgb), 0.86);
        padding: 1.1rem;
        cursor: pointer;
        transition: border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
    }

    .landing-scenario-card:hover {
        transform: translateY(-2px);
        border-color: rgba(var(--bs-primary-rgb), 0.5);
    }

    .landing-scenario-card.is-selected {
        border-color: rgba(var(--bs-primary-rgb), 0.85);
        box-shadow: 0 18px 38px rgba(var(--bs-primary-rgb), 0.15);
        background: rgba(var(--bs-primary-rgb), 0.06);
    }

    .landing-scenario-badge {
        display: inline-flex;
        padding: 0.35rem 0.7rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        background: rgba(var(--bs-primary-rgb), 0.12);
        color: rgb(var(--bs-primary-rgb));
        margin-bottom: 0.85rem;
    }

    .landing-scenario-card h6 {
        margin-bottom: 0.45rem;
    }

    .landing-scenario-card p {
        margin-bottom: 0;
        color: rgba(var(--bs-body-color-rgb), 0.72);
        font-size: 0.94rem;
    }

    .landing-requests-list {
        display: grid;
        gap: 0.85rem;
    }

    .landing-request-item {
        border: 1px solid rgba(var(--bs-body-color-rgb), 0.1);
        border-radius: 1rem;
        padding: 1rem;
        background: rgba(var(--bs-body-bg-rgb), 0.65);
    }

    .landing-request-item strong {
        display: block;
        margin-bottom: 0.25rem;
    }
</style>

<div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="mb-1">{{ __('landings.form.title') }}</h4>
        <p class="text-muted mb-0">{{ __('landings.form.subtitle') }}</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
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
    <input type="hidden" id="landing-template" name="landing" />

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-lg-8">
                    <label for="landing-title" class="form-label">{{ __('landings.form.fields.title') }}</label>
                    <input type="text" class="form-control" id="landing-title" name="title" required />
                    <div class="invalid-feedback">{{ __('landings.validation.title_required') }}</div>
                </div>
                <div class="col-lg-4">
                    <label class="form-label d-flex align-items-center justify-content-between">
                        <span>{{ __('landings.form.fields.status') }}</span>
                        <span class="badge bg-label-success" id="landing-status-indicator">{{ __('landings.statuses.active') }}</span>
                    </label>
                    <div class="form-check form-switch pt-2">
                        <input class="form-check-input" type="checkbox" id="landing-active" name="is_active" checked />
                        <label class="form-check-label" for="landing-active">{{ __('landings.form.fields.is_active_label') }}</label>
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
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header pb-0">
            <h5 class="card-title mb-0">{{ __('landings.form.sections.template_title') }}</h5>
            <p class="text-muted small mb-0">{{ __('landings.form.sections.template_hint') }}</p>
        </div>
        <div class="card-body">
            <div class="landing-scenario-grid" id="landing-scenario-grid">
                <label class="landing-scenario-card" data-scenario-option>
                    <input class="visually-hidden" type="radio" name="type" value="general" data-template="landings.templates.general" checked />
                    <span class="landing-scenario-badge">{{ __('landings.templates.general.card_badge') }}</span>
                    <h6>{{ __('landings.types.general') }}</h6>
                    <p>{{ __('landings.templates.general.card_description') }}</p>
                </label>
                <label class="landing-scenario-card" data-scenario-option>
                    <input class="visually-hidden" type="radio" name="type" value="promotion" data-template="landings.templates.promotion" />
                    <span class="landing-scenario-badge">{{ __('landings.templates.promotion.card_badge') }}</span>
                    <h6>{{ __('landings.types.promotion') }}</h6>
                    <p>{{ __('landings.templates.promotion.card_description') }}</p>
                </label>
                <label class="landing-scenario-card" data-scenario-option>
                    <input class="visually-hidden" type="radio" name="type" value="service" data-template="landings.templates.service" />
                    <span class="landing-scenario-badge">{{ __('landings.templates.service.card_badge') }}</span>
                    <h6>{{ __('landings.types.service') }}</h6>
                    <p>{{ __('landings.templates.service.card_description') }}</p>
                </label>
                <label class="landing-scenario-card" data-scenario-option>
                    <input class="visually-hidden" type="radio" name="type" value="seasonal" data-template="landings.templates.seasonal" />
                    <span class="landing-scenario-badge">{{ __('landings.templates.seasonal.card_badge') }}</span>
                    <h6>{{ __('landings.types.seasonal') }}</h6>
                    <p>{{ __('landings.templates.seasonal.card_description') }}</p>
                </label>
                <label class="landing-scenario-card" data-scenario-option>
                    <input class="visually-hidden" type="radio" name="type" value="consultation" data-template="landings.templates.consultation" />
                    <span class="landing-scenario-badge">{{ __('landings.templates.consultation.card_badge') }}</span>
                    <h6>{{ __('landings.types.consultation') }}</h6>
                    <p>{{ __('landings.templates.consultation.card_description') }}</p>
                </label>
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
                <div class="col-md-8">
                    <label for="landing-subtitle" class="form-label">{{ __('landings.form.fields.subtitle') }}</label>
                    <textarea class="form-control" id="landing-subtitle" name="settings[subtitle]" rows="2"></textarea>
                </div>
                <div class="col-md-4">
                    <label for="landing-booking-hint" class="form-label">{{ __('landings.form.fields.booking_hint') }}</label>
                    <input type="text" class="form-control" id="landing-booking-hint" name="settings[booking_hint]" />
                </div>
                <div class="col-md-6">
                    <label for="landing-cta-label" class="form-label">{{ __('landings.form.fields.cta_label') }}</label>
                    <input type="text" class="form-control" id="landing-cta-label" name="settings[cta_label]" />
                </div>
                <div class="col-md-6">
                    <label for="landing-secondary-cta-label" class="form-label">{{ __('landings.form.fields.secondary_cta_label') }}</label>
                    <input type="text" class="form-control" id="landing-secondary-cta-label" name="settings[secondary_cta_label]" />
                </div>
                <div class="col-md-4">
                    <label for="landing-phone" class="form-label">{{ __('landings.form.fields.phone') }}</label>
                    <input type="text" class="form-control" id="landing-phone" name="settings[phone]" />
                </div>
                <div class="col-md-4">
                    <label for="landing-whatsapp" class="form-label">{{ __('landings.form.fields.whatsapp_url') }}</label>
                    <input type="url" class="form-control" id="landing-whatsapp" name="settings[whatsapp_url]" placeholder="https://wa.me/..." />
                </div>
                <div class="col-md-4">
                    <label for="landing-telegram" class="form-label">{{ __('landings.form.fields.telegram_url') }}</label>
                    <input type="url" class="form-control" id="landing-telegram" name="settings[telegram_url]" placeholder="https://t.me/..." />
                </div>
                <div class="col-12">
                    <label for="landing-address" class="form-label">{{ __('landings.form.fields.address') }}</label>
                    <input type="text" class="form-control" id="landing-address" name="settings[address]" />
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header pb-0">
            <h5 class="card-title mb-0">{{ __('landings.form.sections.social_title') }}</h5>
            <p class="text-muted small mb-0">{{ __('landings.form.sections.social_hint') }}</p>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="landing-proof-items" class="form-label">{{ __('landings.form.fields.proof_items_text') }}</label>
                    <textarea class="form-control" id="landing-proof-items" name="settings[proof_items_text]" rows="5" placeholder="{{ __('landings.form.placeholders.multiline') }}"></textarea>
                </div>
                <div class="col-md-6">
                    <label for="landing-faq-items" class="form-label">{{ __('landings.form.fields.faq_items_text') }}</label>
                    <textarea class="form-control" id="landing-faq-items" name="settings[faq_items_text]" rows="5" placeholder="{{ __('landings.form.placeholders.multiline') }}"></textarea>
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
            <div class="row g-3">
                <div class="col-12">
                    <label for="landing-greeting" class="form-label">{{ __('landings.form.fields.greeting') }}</label>
                    <textarea class="form-control" id="landing-greeting" name="settings[greeting]" rows="3"></textarea>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch pt-4">
                        <input class="form-check-input" type="checkbox" id="landing-all-services" name="settings[show_all_services]" />
                        <label class="form-check-label" for="landing-all-services">{{ __('landings.form.fields.show_all_services') }}</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="landing-general-bonus" class="form-label">{{ __('landings.form.fields.bonus_text') }}</label>
                    <input type="text" class="form-control" id="landing-general-bonus" name="settings[bonus_text]" />
                </div>
                <div class="col-12">
                    <label for="landing-service-ids" class="form-label">{{ __('landings.form.fields.service_ids') }}</label>
                    <select class="form-select" id="landing-service-ids" name="settings[service_ids][]" multiple></select>
                    <div class="form-text">{{ __('landings.form.helpers.service_ids') }}</div>
                </div>
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
                    <label for="landing-promo-service" class="form-label">{{ __('landings.form.fields.service') }}</label>
                    <select class="form-select" id="landing-promo-service" name="settings[service_id]">
                        <option value="">{{ __('landings.form.placeholders.service') }}</option>
                    </select>
                </div>
                <div class="col-12">
                    <label for="landing-headline" class="form-label">{{ __('landings.form.fields.headline') }}</label>
                    <input type="text" class="form-control" id="landing-headline" name="settings[headline]" />
                </div>
                <div class="col-12">
                    <label for="landing-description" class="form-label">{{ __('landings.form.fields.description') }}</label>
                    <textarea class="form-control" id="landing-description" name="settings[description]" rows="3"></textarea>
                </div>
                <div class="col-md-3">
                    <label for="landing-discount" class="form-label">{{ __('landings.form.fields.discount_percent') }}</label>
                    <input type="number" class="form-control" id="landing-discount" name="settings[discount_percent]" min="0" max="100" step="0.1" />
                </div>
                <div class="col-md-3">
                    <label for="landing-promo-code" class="form-label">{{ __('landings.form.fields.promo_code') }}</label>
                    <input type="text" class="form-control" id="landing-promo-code" name="settings[promo_code]" />
                </div>
                <div class="col-md-3">
                    <label for="landing-ends-at" class="form-label">{{ __('landings.form.fields.ends_at') }}</label>
                    <input type="date" class="form-control" id="landing-ends-at" name="settings[ends_at]" />
                </div>
                <div class="col-md-3">
                    <label for="landing-promo-bonus" class="form-label">{{ __('landings.form.fields.bonus_text') }}</label>
                    <input type="text" class="form-control" id="landing-promo-bonus" name="settings[bonus_text]" />
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
                <div class="col-md-3">
                    <label for="landing-price-from" class="form-label">{{ __('landings.form.fields.price_from') }}</label>
                    <input type="text" class="form-control" id="landing-price-from" name="settings[price_from]" />
                </div>
                <div class="col-md-3">
                    <label for="landing-duration-label" class="form-label">{{ __('landings.form.fields.duration_label') }}</label>
                    <input type="text" class="form-control" id="landing-duration-label" name="settings[duration_label]" />
                </div>
                <div class="col-12">
                    <label for="landing-service-description" class="form-label">{{ __('landings.form.fields.service_description') }}</label>
                    <textarea class="form-control" id="landing-service-description" name="settings[service_description]" rows="3"></textarea>
                </div>
                <div class="col-12">
                    <label for="landing-benefit-items" class="form-label">{{ __('landings.form.fields.benefit_items_text') }}</label>
                    <textarea class="form-control" id="landing-benefit-items" name="settings[benefit_items_text]" rows="4" placeholder="{{ __('landings.form.placeholders.multiline') }}"></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4 d-none" data-type-section="seasonal">
        <div class="card-header pb-0">
            <h5 class="card-title mb-0">{{ __('landings.form.sections.seasonal_title') }}</h5>
            <p class="text-muted small mb-0">{{ __('landings.form.sections.seasonal_hint') }}</p>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="landing-season-label" class="form-label">{{ __('landings.form.fields.season_label') }}</label>
                    <input type="text" class="form-control" id="landing-season-label" name="settings[season_label]" />
                </div>
                <div class="col-md-4">
                    <label for="landing-season-ends-at" class="form-label">{{ __('landings.form.fields.ends_at') }}</label>
                    <input type="date" class="form-control" id="landing-season-ends-at" name="settings[ends_at]" />
                </div>
                <div class="col-md-4">
                    <label for="landing-season-bonus" class="form-label">{{ __('landings.form.fields.bonus_text') }}</label>
                    <input type="text" class="form-control" id="landing-season-bonus" name="settings[bonus_text]" />
                </div>
                <div class="col-12">
                    <label for="landing-season-headline" class="form-label">{{ __('landings.form.fields.headline') }}</label>
                    <input type="text" class="form-control" id="landing-season-headline" name="settings[headline]" />
                </div>
                <div class="col-12">
                    <label for="landing-season-description" class="form-label">{{ __('landings.form.fields.description') }}</label>
                    <textarea class="form-control" id="landing-season-description" name="settings[description]" rows="3"></textarea>
                </div>
                <div class="col-12">
                    <label for="landing-season-service-ids" class="form-label">{{ __('landings.form.fields.service_ids') }}</label>
                    <select class="form-select" id="landing-season-service-ids" name="settings[service_ids][]" multiple></select>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4 d-none" data-type-section="consultation">
        <div class="card-header pb-0">
            <h5 class="card-title mb-0">{{ __('landings.form.sections.consultation_title') }}</h5>
            <p class="text-muted small mb-0">{{ __('landings.form.sections.consultation_hint') }}</p>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="landing-consultation-service" class="form-label">{{ __('landings.form.fields.service') }}</label>
                    <select class="form-select" id="landing-consultation-service" name="settings[service_id]">
                        <option value="">{{ __('landings.form.placeholders.service') }}</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="landing-lead-magnet" class="form-label">{{ __('landings.form.fields.lead_magnet') }}</label>
                    <input type="text" class="form-control" id="landing-lead-magnet" name="settings[lead_magnet]" />
                </div>
                <div class="col-12">
                    <label for="landing-consultation-headline" class="form-label">{{ __('landings.form.fields.headline') }}</label>
                    <input type="text" class="form-control" id="landing-consultation-headline" name="settings[headline]" />
                </div>
                <div class="col-12">
                    <label for="landing-consultation-description" class="form-label">{{ __('landings.form.fields.description') }}</label>
                    <textarea class="form-control" id="landing-consultation-description" name="settings[description]" rows="3"></textarea>
                </div>
                <div class="col-12">
                    <label for="landing-consultation-benefits" class="form-label">{{ __('landings.form.fields.benefit_items_text') }}</label>
                    <textarea class="form-control" id="landing-consultation-benefits" name="settings[benefit_items_text]" rows="4" placeholder="{{ __('landings.form.placeholders.multiline') }}"></textarea>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="card d-none" id="landing-requests-card">
    <div class="card-header pb-0">
        <h5 class="card-title mb-0">{{ __('landings.form.sections.requests_title') }}</h5>
        <p class="text-muted small mb-0">{{ __('landings.form.sections.requests_hint') }}</p>
    </div>
    <div class="card-body">
        <div class="landing-requests-list" id="landing-requests-list"></div>
        <div class="text-muted d-none" id="landing-requests-empty">{{ __('landings.form.requests_empty') }}</div>
    </div>
</div>

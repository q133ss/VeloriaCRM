{{--
    The one place a language model is used on this screen. Which clients are due
    is arithmetic and already decided by the time this opens; all that is left is
    the wording, which is the part a master actually puts off.
--}}
<div class="modal fade outreach-modal" id="outreachModal" tabindex="-1" aria-labelledby="outreachTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="outreach-title" id="outreachTitle">{{ __('dashboard.outreach.title') }}</h2>
                    <p class="outreach-subtitle mb-0" data-outreach-client></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('dashboard.outreach.close') }}"></button>
            </div>
            <div class="modal-body">
                <label class="form-label" for="outreach-text">{{ __('dashboard.outreach.label') }}</label>
                <textarea class="form-control outreach-text" id="outreach-text" rows="5" data-outreach-text></textarea>

                <p class="outreach-note" data-outreach-note hidden></p>
                <p class="outreach-error" data-outreach-error hidden></p>

                <div class="outreach-actions">
                    <button type="button" class="btn btn-outline-secondary" data-outreach-regenerate>
                        {{ __('dashboard.outreach.regenerate') }}
                    </button>
                    <button type="button" class="btn btn-outline-primary" data-outreach-copy>
                        {{ __('dashboard.outreach.copy') }}
                    </button>
                    <a class="btn btn-primary" data-outreach-send target="_blank" rel="noopener">
                        {{ __('dashboard.outreach.open_whatsapp') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

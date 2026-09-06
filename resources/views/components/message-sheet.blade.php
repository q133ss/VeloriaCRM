{{--
    One message sheet for the whole app.

    Which client to write to is arithmetic, decided before this opens; all that
    is left is the wording, which is the part a master actually puts off. Copy
    always works — plenty of masters talk to clients in messengers this app has
    no access to — and sending is offered only when a channel is really there.
--}}
<div class="modal fade outreach-modal" id="messageSheet" tabindex="-1" aria-labelledby="messageSheetTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="outreach-title" id="messageSheetTitle">{{ __('dashboard.outreach.title') }}</h2>
                    <p class="outreach-subtitle mb-0" data-message-client></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('dashboard.outreach.close') }}"></button>
            </div>
            <div class="modal-body">
                <label class="form-label" for="message-sheet-text">{{ __('dashboard.outreach.label') }}</label>
                <textarea class="form-control outreach-text" id="message-sheet-text" rows="5" data-message-text></textarea>

                <p class="outreach-note" data-message-note hidden></p>
                <p class="outreach-error" data-message-error hidden></p>

                <div class="outreach-actions">
                    <button type="button" class="btn btn-outline-secondary" data-message-regenerate hidden>
                        {{ __('dashboard.outreach.regenerate') }}
                    </button>
                    <button type="button" class="btn btn-outline-primary" data-message-copy>
                        {{ __('dashboard.outreach.copy') }}
                    </button>
                    <button type="button" class="btn btn-primary" data-message-send hidden></button>
                    <a class="btn btn-primary" data-message-whatsapp target="_blank" rel="noopener" hidden>
                        {{ __('dashboard.outreach.open_whatsapp') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

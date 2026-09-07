{{--
    One line above the create form: the master writes the booking the way she
    would say it out loud, and the fields below fill in. Nothing is ever
    submitted from here — she still presses the button herself.
--}}
<div class="booking-phrase" data-booking-phrase>
    <div class="booking-phrase__row">
        <span class="booking-phrase__spark" aria-hidden="true"><i class="ri ri-sparkling-2-line"></i></span>

        {{-- type=text, not a form control with a name: this never reaches the order payload. --}}
        <input
            type="text"
            class="form-control booking-phrase__input"
            id="booking-phrase-text"
            placeholder="{{ __('calendar.phrase.placeholder') }}"
            autocomplete="off"
            maxlength="200"
            aria-describedby="booking-phrase-status"
        />

        {{-- Every button here is type=button on purpose: the default type
             inside <form> submits the order. --}}
        <button
            type="button"
            class="btn booking-phrase__icon"
            data-booking-phrase-mic
            aria-label="{{ __('calendar.phrase.mic_label') }}"
        >
            <i class="ri ri-mic-line"></i>
        </button>

        <button
            type="button"
            class="btn btn-primary booking-phrase__submit"
            data-booking-phrase-submit
            aria-label="{{ __('calendar.phrase.submit') }}"
        >
            <i class="ri ri-arrow-right-line"></i>
        </button>
    </div>

    <div class="booking-phrase__status" id="booking-phrase-status" role="status" aria-live="polite"></div>
    <div class="booking-phrase__availability d-none" id="booking-phrase-availability"></div>
    <div class="booking-phrase__choices" id="booking-phrase-choices"></div>
</div>

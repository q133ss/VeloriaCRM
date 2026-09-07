{{--
    The visit currently running, in the header of every page.

    A master starts a booking and then walks away from the calendar — to a
    client card, to the price list, to messages. Without this the only way to
    know the timer is still going is to remember, and a forgotten Finish is
    exactly what turns a measured duration into nine hundred minutes.
--}}
<li class="nav-item active-timer me-2 me-lg-3 d-none" id="active-timer" data-active-timer>
    <a class="active-timer__link" href="#" data-active-timer-link>
        <span class="active-timer__pulse" aria-hidden="true"></span>
        <span class="active-timer__body">
            <span class="active-timer__elapsed" data-active-timer-elapsed>0:00</span>
            <span class="active-timer__client" data-active-timer-client></span>
        </span>
    </a>
    <button
        type="button"
        class="active-timer__finish"
        data-active-timer-finish
        data-tooltip="{{ __('calendar.day.timer.stop') }}"
        aria-label="{{ __('calendar.day.timer.stop') }}"
    >
        <span class="active-timer__stop" aria-hidden="true"></span>
    </button>
</li>

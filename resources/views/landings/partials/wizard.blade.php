@php($w = __('landings.wizard'))

<style>
    .lw { max-width: 1040px; margin: 0 auto; }
    .lw-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; margin-bottom: 1.25rem; }
    .lw-head h4 { margin: 0; }
    .lw-back-link { font-size: .9rem; color: rgba(var(--bs-body-color-rgb), .7); text-decoration: none; }
    .lw-back-link:hover { color: rgb(var(--bs-primary-rgb)); }

    .lw-progress { display: flex; align-items: center; gap: .5rem; margin-bottom: 1.75rem; }
    .lw-progress-step { flex: 1; height: .375rem; border-radius: 999px; background: rgba(var(--bs-body-color-rgb), .12); transition: background .2s ease; }
    .lw-progress-step.is-done { background: rgba(var(--bs-primary-rgb), .45); }
    .lw-progress-step.is-current { background: rgb(var(--bs-primary-rgb)); }
    .lw-progress-label { font-size: .875rem; color: rgba(var(--bs-body-color-rgb), .7); margin-bottom: .5rem; }

    .lw-step { display: none; }
    .lw-step.is-active { display: block; }
    .lw-step > h5 { font-size: 1.5rem; margin-bottom: .35rem; }
    .lw-hint { color: rgba(var(--bs-body-color-rgb), .72); margin-bottom: 1.5rem; }

    .lw-goals { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 1rem; }
    .lw-goal { position: relative; display: flex; flex-direction: column; border: 2px solid rgba(var(--bs-body-color-rgb), .12); border-radius: 1.25rem; padding: 1rem; background: rgba(var(--bs-body-bg-rgb), .9); cursor: pointer; transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease; }
    .lw-goal:hover { transform: translateY(-2px); border-color: rgba(var(--bs-primary-rgb), .5); }
    .lw-goal:focus-within { outline: 3px solid rgba(var(--bs-primary-rgb), .35); outline-offset: 2px; }
    .lw-goal.is-selected { border-color: rgb(var(--bs-primary-rgb)); box-shadow: 0 14px 32px rgba(var(--bs-primary-rgb), .16); background: rgba(var(--bs-primary-rgb), .05); }
    .lw-goal input { position: absolute; opacity: 0; pointer-events: none; }
    .lw-goal h6 { font-size: 1.1rem; margin: .9rem 0 .3rem; }
    .lw-goal p { margin: 0 0 .5rem; color: rgba(var(--bs-body-color-rgb), .75); font-size: .95rem; }
    .lw-goal-example { font-size: .85rem; color: rgba(var(--bs-body-color-rgb), .6); font-style: italic; margin-bottom: .9rem; }
    .lw-goal-cta { margin-top: auto; display: inline-flex; align-items: center; justify-content: center; min-height: 2.75rem; padding: 0 1.1rem; border-radius: 999px; font-weight: 600; background: rgba(var(--bs-body-color-rgb), .08); color: var(--bs-body-color); }
    .lw-goal.is-selected .lw-goal-cta { background: rgb(var(--bs-primary-rgb)); color: #fff; }

    /* mini mock of the public page */
    .lw-mock { --lw-c: #7f5af0; --lw-bg: linear-gradient(160deg, #fffaf8, #f7eef2); border-radius: 1rem; padding: .85rem; background: var(--lw-bg); border: 1px solid rgba(0, 0, 0, .06); color: #2b2733; min-height: 9.5rem; overflow: hidden; }
    .lw-mock.is-dark { color: #f4f1fa; }
    .lw-mock-brand { display: flex; align-items: center; gap: .4rem; font-weight: 700; font-size: .8rem; margin-bottom: .6rem; }
    .lw-mock-mark { width: 1.2rem; height: 1.2rem; border-radius: .4rem; background: var(--lw-c); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: .65rem; }
    .lw-mock-title { font-weight: 700; font-size: .95rem; line-height: 1.2; margin-bottom: .35rem; }
    .lw-mock-line { height: .35rem; border-radius: 99px; background: currentColor; opacity: .16; margin-bottom: .3rem; }
    .lw-mock-line.short { width: 60%; }
    .lw-mock-row { display: flex; gap: .3rem; flex-wrap: wrap; margin: .5rem 0; }
    .lw-mock-chip { font-size: .62rem; font-weight: 600; padding: .15rem .45rem; border-radius: 99px; background: rgba(255, 255, 255, .7); color: var(--lw-c); border: 1px solid var(--lw-c); }
    .lw-mock.is-dark .lw-mock-chip { background: rgba(255, 255, 255, .1); }
    .lw-mock-badge { display: inline-block; font-size: .7rem; font-weight: 700; padding: .15rem .5rem; border-radius: 99px; background: var(--lw-c); color: #fff; margin-bottom: .4rem; }
    .lw-mock-btn { display: inline-block; margin-top: .25rem; font-size: .68rem; font-weight: 700; padding: .3rem .8rem; border-radius: 99px; background: var(--lw-c); color: #fff; }

    .lw-subhead { font-size: 1rem; margin-bottom: .6rem; }
    .lw-layouts { display: grid; gap: .75rem; max-width: 460px; }
    .lw-layout { position: relative; display: flex; gap: .85rem; align-items: center; padding: .7rem; border: 2px solid rgba(var(--bs-body-color-rgb), .12); border-radius: 1rem; cursor: pointer; background: rgba(var(--bs-body-bg-rgb), .9); }
    .lw-layout input { position: absolute; opacity: 0; pointer-events: none; }
    .lw-layout:focus-within { outline: 3px solid rgba(var(--bs-primary-rgb), .35); outline-offset: 2px; }
    .lw-layout.is-selected { border-color: rgb(var(--bs-primary-rgb)); box-shadow: 0 10px 24px rgba(var(--bs-primary-rgb), .14); }
    .lw-layout-thumb { width: 5.5rem; height: 5.5rem; flex: none; border-radius: .7rem; overflow: hidden; background: #f3efe9; }
    .lw-layout-thumb img { width: 100%; height: 100%; object-fit: cover; object-position: top; display: block; }
    .lw-layout-thumb .lw-mock { min-height: 0; height: 100%; border-radius: 0; padding: .4rem; }
    .lw-layout strong { display: block; margin-bottom: .15rem; }
    .lw-layout span.lw-layout-desc { font-size: .875rem; color: rgba(var(--bs-body-color-rgb), .72); }
    .lw-themes.is-locked { opacity: .4; pointer-events: none; }
    .lw-phone img.lw-salone-shot { width: 100%; display: block; border-radius: 1.2rem; }
    .lw-style { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 340px); gap: 2rem; align-items: start; }
    .lw-themes { display: grid; gap: .75rem; max-width: 360px; }
    .lw-theme { position: relative; display: flex; align-items: center; gap: .7rem; min-height: 3.5rem; padding: .6rem .85rem; border: 2px solid rgba(var(--bs-body-color-rgb), .12); border-radius: 1rem; cursor: pointer; background: rgba(var(--bs-body-bg-rgb), .9); font-weight: 600; }
    .lw-theme input { position: absolute; opacity: 0; pointer-events: none; }
    .lw-theme:focus-within { outline: 3px solid rgba(var(--bs-primary-rgb), .35); outline-offset: 2px; }
    .lw-theme.is-selected { border-color: rgb(var(--bs-primary-rgb)); box-shadow: 0 10px 24px rgba(var(--bs-primary-rgb), .14); }
    .lw-swatch { width: 1.75rem; height: 1.75rem; border-radius: 50%; flex: none; box-shadow: inset 0 0 0 2px rgba(255, 255, 255, .55), 0 0 0 1px rgba(0, 0, 0, .12); }
    .lw-phone { border: .5rem solid rgba(var(--bs-body-color-rgb), .85); border-radius: 1.75rem; padding: .25rem; background: rgba(var(--bs-body-bg-rgb), 1); }
    .lw-phone .lw-mock { min-height: 17rem; border-radius: 1.2rem; padding: 1.1rem; }
    .lw-phone .lw-mock-title { font-size: 1.15rem; }
    .lw-phone .lw-mock-brand { font-size: .9rem; margin-bottom: 1rem; }
    .lw-phone .lw-mock-btn { font-size: .8rem; padding: .5rem 1.1rem; }
    .lw-phone .lw-mock-chip { font-size: .72rem; }
    .lw-phone .lw-mock-line { height: .45rem; margin-bottom: .4rem; }

    .lw-card { border: 1px solid rgba(var(--bs-body-color-rgb), .12); border-radius: 1.25rem; padding: 1.25rem; background: rgba(var(--bs-body-bg-rgb), .9); }
    .lw-form { display: grid; gap: 1.1rem; max-width: 640px; }
    .lw-form .form-control, .lw-form .form-select { min-height: 3rem; font-size: 1rem; }
    .lw-form .lw-label { font-weight: 600; margin-bottom: .35rem; display: block; }
    #lw-type-block { display: grid; gap: 1.1rem; }
    #lw-type-block:empty { display: none; }
    .lw-field-error { color: var(--bs-danger); font-size: .875rem; margin-top: .3rem; display: none; }
    .lw-field.has-error .lw-field-error { display: block; }
    .lw-field.has-error .form-control, .lw-field.has-error .form-select { border-color: var(--bs-danger); }
    .lw-check-list { display: grid; gap: .5rem; }
    .lw-check { display: flex; align-items: center; gap: .7rem; min-height: 3rem; padding: .5rem .85rem; border: 1px solid rgba(var(--bs-body-color-rgb), .14); border-radius: .9rem; cursor: pointer; }
    .lw-check input { width: 1.25rem; height: 1.25rem; flex: none; }
    .lw-check:has(input:checked) { border-color: rgba(var(--bs-primary-rgb), .7); background: rgba(var(--bs-primary-rgb), .06); }
    .lw-seg { display: inline-flex; padding: .25rem; border-radius: 999px; background: rgba(var(--bs-body-color-rgb), .08); gap: .25rem; }
    .lw-seg label { margin: 0; padding: .55rem 1rem; border-radius: 999px; cursor: pointer; font-weight: 600; min-height: 2.5rem; display: inline-flex; align-items: center; }
    .lw-seg input { position: absolute; opacity: 0; pointer-events: none; }
    .lw-seg label:has(input:checked) { background: rgb(var(--bs-primary-rgb)); color: #fff; }
    .lw-seg label:has(input:focus-visible) { outline: 3px solid rgba(var(--bs-primary-rgb), .35); }
    .lw-chips { display: flex; flex-wrap: wrap; gap: .5rem; }
    .lw-chip { position: relative; }
    .lw-chip input { position: absolute; opacity: 0; pointer-events: none; }
    .lw-chip span { display: inline-flex; align-items: center; min-height: 2.75rem; padding: 0 1rem; border-radius: 999px; border: 1px solid rgba(var(--bs-body-color-rgb), .18); cursor: pointer; font-weight: 600; }
    .lw-chip input:checked + span { background: rgb(var(--bs-primary-rgb)); border-color: transparent; color: #fff; }
    .lw-chip input:focus-visible + span { outline: 3px solid rgba(var(--bs-primary-rgb), .35); outline-offset: 2px; }
    .lw-empty { border: 1px dashed rgba(var(--bs-body-color-rgb), .25); border-radius: 1rem; padding: 1rem; background: rgba(var(--bs-warning-rgb), .08); }
    .lw-empty p { margin-bottom: .6rem; }

    .lw-nav { position: sticky; bottom: 0; z-index: 20; margin: 2rem -.5rem 0; padding: .75rem .5rem calc(.75rem + env(safe-area-inset-bottom)); background: rgba(var(--bs-body-bg-rgb), .94); backdrop-filter: blur(8px); border-top: 1px solid rgba(var(--bs-body-color-rgb), .1); }
    .lw-nav-inner { display: flex; gap: .75rem; justify-content: space-between; }
    .lw-nav .btn { min-height: 3rem; padding-left: 1.6rem; padding-right: 1.6rem; font-weight: 600; }
    .lw-nav .btn-primary { min-width: 11rem; }
    .lw-nav.is-hidden { display: none; }

    /* ---- step 4: the page is ready ---- */
    .lw-done-head { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; }
    .lw-done-check { flex: none; width: 3.5rem; height: 3.5rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; background: rgba(var(--bs-success-rgb), .16); color: rgb(var(--bs-success-rgb)); }
    .lw-done-head h5 { font-size: 1.6rem; margin: 0 0 .15rem; }
    .lw-done-head p { margin: 0; color: rgba(var(--bs-body-color-rgb), .72); }
    .lw-done { display: grid; grid-template-columns: 400px minmax(0, 1fr); gap: 2rem; align-items: start; }
    .lw-done-side { display: grid; gap: 1rem; }
    .lw-panel { border: 1px solid rgba(var(--bs-body-color-rgb), .12); border-radius: 1.25rem; padding: 1.1rem; background: rgba(var(--bs-body-bg-rgb), .9); }
    .lw-panel-title { font-size: .8rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: rgba(var(--bs-body-color-rgb), .6); margin-bottom: .7rem; }
    .lw-link-pill { display: flex; align-items: center; gap: .4rem; padding: .3rem .3rem .3rem 1rem; border: 1px solid rgba(var(--bs-body-color-rgb), .18); border-radius: 999px; background: rgba(var(--bs-body-bg-rgb), 1); }
    .lw-link-pill input { flex: 1; min-width: 0; border: 0; outline: 0; background: transparent; color: inherit; font-size: 1rem; padding: .4rem 0; text-overflow: ellipsis; }
    .lw-copy { flex: none; display: inline-flex; align-items: center; gap: .4rem; min-height: 2.75rem; padding: 0 1.1rem; border: 0; border-radius: 999px; background: rgb(var(--bs-primary-rgb)); color: #fff; font-weight: 600; cursor: pointer; }
    .lw-copy.is-copied { background: rgb(var(--bs-success-rgb)); }
    .lw-share { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; margin-top: .8rem; }
    .lw-share span { font-size: .9rem; color: rgba(var(--bs-body-color-rgb), .65); margin-right: .15rem; }
    .lw-share a { display: inline-flex; align-items: center; gap: .4rem; min-height: 2.5rem; padding: 0 .95rem; border-radius: 999px; border: 1px solid rgba(var(--bs-body-color-rgb), .18); color: inherit; text-decoration: none; font-weight: 600; font-size: .92rem; }
    .lw-share a:hover { border-color: rgb(var(--bs-primary-rgb)); color: rgb(var(--bs-primary-rgb)); }
    .lw-action { display: flex; align-items: center; gap: .9rem; width: 100%; padding: .9rem 1rem; border-radius: 1rem; border: 1px solid rgba(var(--bs-body-color-rgb), .14); background: transparent; color: inherit; text-decoration: none; text-align: left; transition: border-color .15s ease, transform .15s ease; }
    .lw-action + .lw-action { margin-top: .6rem; }
    .lw-action:hover { border-color: rgba(var(--bs-primary-rgb), .6); transform: translateY(-1px); color: inherit; }
    .lw-action-icon { flex: none; width: 2.6rem; height: 2.6rem; border-radius: .8rem; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; background: rgba(var(--bs-primary-rgb), .12); color: rgb(var(--bs-primary-rgb)); }
    .lw-action strong { display: block; font-size: 1rem; color: var(--bs-emphasis-color, inherit); }
    .lw-action.is-primary strong { color: #fff; }
    .lw-action small { display: block; color: rgba(var(--bs-body-color-rgb), .65); font-size: .85rem; line-height: 1.3; }
    .lw-action.is-primary { background: rgb(var(--bs-primary-rgb)); border-color: transparent; color: #fff; box-shadow: 0 10px 24px rgba(var(--bs-primary-rgb), .3); }
    .lw-action.is-primary:hover { color: #fff; }
    .lw-action.is-primary .lw-action-icon { background: rgba(255, 255, 255, .22); color: #fff; }
    .lw-action.is-primary small { color: rgba(255, 255, 255, .85); }
    .lw-preview { min-width: 0; position: sticky; top: 1rem; }
    .lw-preview-head { display: flex; justify-content: center; margin-bottom: 1rem; }
    .lw-stage { display: flex; justify-content: center; }
    .lw-holder { position: relative; overflow: hidden; background: #fff; }
    .lw-preview.is-phone .lw-holder { border: 7px solid #26213a; border-radius: 2.2rem; box-sizing: content-box; box-shadow: 0 24px 50px rgba(20, 15, 40, .22); }
    .lw-preview.is-desktop .lw-holder { border: 1px solid rgba(var(--bs-body-color-rgb), .18); border-radius: .75rem; box-shadow: 0 16px 40px rgba(20, 15, 40, .16); }
    .lw-frame { display: block; border: 0; background: #fff; transform-origin: top left; }
    .lw-frame-note { text-align: center; font-size: .85rem; color: rgba(var(--bs-body-color-rgb), .6); margin-top: .9rem; }

    @media (max-width: 767.98px) {
        .lw-style, .lw-done { grid-template-columns: 1fr; }
        .lw-phone { max-width: 340px; margin: 0 auto; }
        .lw-preview { position: static; }
        .lw-done-head h5 { font-size: 1.35rem; }
        .lw-copy span { display: none; }
        .lw-step > h5 { font-size: 1.3rem; }
        .lw-nav .btn { flex: 1; padding-left: .5rem; padding-right: .5rem; }
        .lw-nav .btn-primary { min-width: 0; flex: 2; }
        .lw-progress-label { margin-bottom: .35rem; }
    }
</style>

<div class="lw" id="lw-root">
    <div class="lw-head">
        <h4>{{ $w['title'] }}</h4>
        <a class="lw-back-link" href="{{ route('landings.index') }}">← {{ $w['to_list'] }}</a>
    </div>

    <div class="lw-progress-label" id="lw-progress-label" aria-live="polite"></div>
    <div class="lw-progress" id="lw-progress" aria-hidden="true">
        <span class="lw-progress-step"></span><span class="lw-progress-step"></span><span class="lw-progress-step"></span><span class="lw-progress-step"></span>
    </div>

    <div id="lw-alerts" role="alert"></div>

    {{-- Step 1: goal --}}
    <section class="lw-step" data-step="1" aria-labelledby="lw-h1">
        <h5 id="lw-h1">{{ $w['goal']['title'] }}</h5>
        <p class="lw-hint">{{ $w['goal']['hint'] }}</p>
        <div class="lw-goals" id="lw-goals" role="radiogroup" aria-labelledby="lw-h1"></div>
    </section>

    {{-- Step 2: look --}}
    <section class="lw-step" data-step="2" aria-labelledby="lw-h2">
        <h5 id="lw-h2">{{ $w['style']['title'] }}</h5>
        <p class="lw-hint">{{ $w['style']['hint'] }}</p>
        <div class="lw-style">
            <div>
                <h6 class="lw-subhead" id="lw-layout-h">{{ $w['style']['layout_title'] }}</h6>
                <div class="lw-layouts" id="lw-layouts" role="radiogroup" aria-labelledby="lw-layout-h"></div>
            </div>
            <div class="lw-phone" aria-hidden="true"><div id="lw-style-mock"></div></div>
        </div>
    </section>

    {{-- Step 3: about --}}
    <section class="lw-step" data-step="3" aria-labelledby="lw-h3">
        <h5 id="lw-h3">{{ $w['about']['title'] }}</h5>
        <p class="lw-hint">{{ $w['about']['hint'] }}</p>
        <form class="lw-form" id="lw-about-form" novalidate autocomplete="off">
            <div class="lw-field" data-field="name">
                <label class="lw-label" for="lw-name">{{ $w['about']['name'] }}</label>
                <input type="text" class="form-control" id="lw-name" maxlength="120" placeholder="{{ $w['about']['name_ph'] }}" />
                <div class="lw-field-error"></div>
            </div>

            <div id="lw-type-block"></div>

            <div class="lw-field" data-field="phone">
                <label class="lw-label" for="lw-phone">{{ $w['about']['phone'] }}</label>
                <input type="tel" class="form-control" id="lw-phone" maxlength="32" inputmode="tel" autocomplete="tel" data-phone-mask placeholder="{{ $w['about']['phone_ph'] }}" />
                <div class="lw-field-error"></div>
                <label class="lw-check mt-2" for="lw-whatsapp">
                    <input type="checkbox" id="lw-whatsapp" />
                    <span>{{ $w['about']['whatsapp'] }}</span>
                </label>
            </div>

            <div class="lw-field" data-field="telegram">
                <label class="lw-label" for="lw-telegram">{{ $w['about']['telegram'] }}</label>
                <input type="text" class="form-control" id="lw-telegram" maxlength="80" placeholder="{{ $w['about']['telegram_ph'] }}" />
                <div class="lw-field-error"></div>
            </div>

            <div class="lw-field" data-field="address">
                <label class="lw-label" for="lw-address">{{ $w['about']['address'] }}</label>
                <input type="text" class="form-control" id="lw-address" maxlength="255" placeholder="{{ $w['about']['address_ph'] }}" />
            </div>
        </form>
    </section>

    {{-- Step 4: done --}}
    <section class="lw-step" data-step="4" aria-labelledby="lw-h4">
        <div class="lw-done-head">
            <div class="lw-done-check" aria-hidden="true">✓</div>
            <div>
                <h5 id="lw-h4">{{ $w['done']['title'] }}</h5>
                <p>{{ $w['done']['hint'] }}</p>
            </div>
        </div>

        <div class="lw-done">
            <div class="lw-done-side">
                <div class="lw-panel">
                    <div class="lw-panel-title">{{ $w['done']['link_title'] }}</div>
                    <div class="lw-link-pill">
                        <input type="text" id="lw-link" readonly aria-label="{{ $w['done']['link_title'] }}" />
                        <button type="button" class="lw-copy" id="lw-copy"><i class="ri ri-file-copy-line" aria-hidden="true"></i><span>{{ $w['done']['copy'] }}</span></button>
                    </div>
                    <div class="lw-share">
                        <span>{{ $w['done']['share'] }}</span>
                        <a id="lw-share-tg" href="#" target="_blank" rel="noopener"><i class="ri ri-telegram-line" aria-hidden="true"></i>Telegram</a>
                        <a id="lw-share-wa" href="#" target="_blank" rel="noopener"><i class="ri ri-whatsapp-line" aria-hidden="true"></i>WhatsApp</a>
                    </div>
                </div>

                <div class="lw-panel">
                    <div class="lw-panel-title">{{ $w['done']['next_title'] }}</div>
                    <a class="lw-action is-primary" id="lw-edit" href="#">
                        <span class="lw-action-icon"><i class="ri ri-edit-2-line" aria-hidden="true"></i></span>
                        <span><strong>{{ $w['done']['edit'] }}</strong><small>{{ $w['done']['edit_sub'] }}</small></span>
                    </a>
                    <a class="lw-action" id="lw-open" href="#" target="_blank" rel="noopener">
                        <span class="lw-action-icon"><i class="ri ri-external-link-line" aria-hidden="true"></i></span>
                        <span><strong>{{ $w['done']['open'] }}</strong><small>{{ $w['done']['open_sub'] }}</small></span>
                    </a>
                    <a class="lw-action" id="lw-settings" href="#">
                        <span class="lw-action-icon"><i class="ri ri-settings-3-line" aria-hidden="true"></i></span>
                        <span><strong>{{ $w['done']['settings'] }}</strong><small>{{ $w['done']['settings_sub'] }}</small></span>
                    </a>
                </div>
            </div>

            <div class="lw-preview is-phone" id="lw-preview">
                <div class="lw-preview-head">
                    <div class="lw-seg" role="radiogroup" aria-label="{{ $w['done']['preview'] }}">
                        <label><input type="radio" name="lw-preview-mode" value="phone" checked />{{ $w['done']['preview_phone'] }}</label>
                        <label><input type="radio" name="lw-preview-mode" value="desktop" />{{ $w['done']['preview_desktop'] }}</label>
                    </div>
                </div>
                <div class="lw-stage" id="lw-stage">
                    <div class="lw-holder" id="lw-holder"><iframe class="lw-frame" id="lw-frame" title="{{ $w['done']['preview'] }}"></iframe></div>
                </div>
                <div class="lw-frame-note">{{ $w['done']['preview'] }}</div>
            </div>
        </div>
    </section>

    <div class="lw-nav" id="lw-nav">
        <div class="lw-nav-inner">
            <button type="button" class="btn btn-outline-secondary" id="lw-prev">← {{ $w['back'] }}</button>
            <button type="button" class="btn btn-primary" id="lw-next">{{ $w['next'] }}</button>
        </div>
    </div>
</div>

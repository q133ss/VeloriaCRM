@php
    $activeTimerLabels = [
        'over' => __('calendar.day.timer.over'),
        'finished' => __('calendar.day.timer.finished'),
        'confirm' => __('calendar.day.timer.confirm'),
        'error' => __('calendar.alerts.day_load_failed'),
    ];
@endphp

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var root = document.getElementById('active-timer');

        if (!root) {
            return;
        }

        var elapsedEl = root.querySelector('[data-active-timer-elapsed]');
        var clientEl = root.querySelector('[data-active-timer-client]');
        var linkEl = root.querySelector('[data-active-timer-link]');
        var finishEl = root.querySelector('[data-active-timer-finish]');

        var LABELS = @json($activeTimerLabels);
        var current = null;
        var tick = null;

        function headers() {
            if (typeof window.authHeaders === 'function') {
                return window.authHeaders();
            }

            var match = document.cookie.match(/(^| )token=([^;]+)/);
            var result = { Accept: 'application/json', 'Content-Type': 'application/json' };

            if (match) {
                result.Authorization = 'Bearer ' + decodeURIComponent(match[2]);
            }

            return result;
        }

        function format(totalSeconds) {
            var hours = Math.floor(totalSeconds / 3600);
            var minutes = Math.floor((totalSeconds % 3600) / 60);
            var seconds = totalSeconds % 60;
            var pad = function (value) { return String(value).padStart(2, '0'); };

            return hours
                ? hours + ':' + pad(minutes) + ':' + pad(seconds)
                : minutes + ':' + pad(seconds);
        }

        function render() {
            if (!current) {
                return;
            }

            var seconds = Math.max(0, Math.floor((Date.now() - current.startedAt) / 1000));
            elapsedEl.textContent = format(seconds);

            // Past the planned length the timer stops being neutral information
            // and starts being the reason the rest of the day runs late.
            var over = current.plannedMinutes > 0 && seconds > current.plannedMinutes * 60;
            root.classList.toggle('is-over', over);
            linkEl.title = over ? LABELS.over : '';
        }

        function show(data) {
            current = {
                id: data.id,
                startedAt: new Date(data.started_at).getTime(),
                plannedMinutes: Number(data.planned_minutes) || 0,
            };

            clientEl.textContent = data.client_name || '';
            linkEl.href = data.url || '#';
            root.classList.remove('d-none');
            finishEl.disabled = false;

            render();

            if (!tick) {
                tick = setInterval(render, 1000);
            }
        }

        function hide() {
            current = null;
            root.classList.add('d-none');
            root.classList.remove('is-over');

            if (tick) {
                clearInterval(tick);
                tick = null;
            }
        }

        function poll() {
            fetch('/api/v1/orders/active', { headers: headers(), credentials: 'include' })
                .then(function (response) { return response.ok ? response.json() : null; })
                .then(function (payload) {
                    var data = payload && payload.data;

                    if (data) {
                        show(data);
                    } else {
                        hide();
                    }
                })
                .catch(function () { /* offline or logged out: leave the last state alone */ });
        }

        finishEl.addEventListener('click', function () {
            if (!current || !window.confirm(LABELS.confirm)) {
                return;
            }

            finishEl.disabled = true;

            fetch('/api/v1/orders/' + current.id + '/complete', {
                method: 'POST',
                headers: headers(),
                credentials: 'include',
            })
                .then(function (response) {
                    if (!response.ok) {
                        finishEl.disabled = false;

                        return;
                    }

                    hide();

                    // The calendar shows the same booking; let it catch up.
                    if (typeof window.veloriaCalendarRefresh === 'function') {
                        window.veloriaCalendarRefresh();
                    }
                })
                .catch(function () { finishEl.disabled = false; });
        });

        poll();

        // A visit runs for an hour or more, so a slow poll is plenty. The tab
        // coming back into focus is the moment the answer is most likely stale.
        setInterval(poll, 60000);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                poll();
            }
        });

        window.veloriaActiveTimer = { refresh: poll };
    });
</script>

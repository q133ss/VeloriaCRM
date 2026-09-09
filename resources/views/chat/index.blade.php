@extends('layouts.app')

@section('title', 'Сообщения')

@section('content')
    <style>
        .chat-page {
            height: calc(100vh - 12rem);
            min-height: 32rem;
        }

        .chat-threads {
            overflow-y: auto;
        }

        .chat-thread-item.active {
            background: rgba(var(--bs-primary-rgb), 0.08);
        }

        .chat-thread-item__name {
            font-weight: 600;
        }

        .chat-thread-item__preview {
            font-size: 0.85rem;
            color: var(--bs-secondary-color);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .chat-thread-item__unread {
            width: 0.55rem;
            height: 0.55rem;
            border-radius: 50%;
            background: var(--bs-primary);
            flex: 0 0 auto;
        }

        .chat-messages {
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .chat-bubble {
            max-width: min(70%, 32rem);
            border-radius: 1rem;
            padding: 0.6rem 0.9rem;
        }

        .chat-bubble--master {
            align-self: flex-end;
            background: var(--bs-primary);
            color: #fff;
        }

        .chat-bubble--client {
            align-self: flex-start;
            background: rgba(var(--bs-heading-color-rgb, 0, 0, 0), 0.06);
        }

        .chat-bubble__meta {
            font-size: 0.72rem;
            opacity: 0.75;
            margin-top: 0.25rem;
        }

        .chat-empty {
            color: var(--bs-secondary-color);
        }
    </style>

    <div class="row g-4 chat-page">
        <div class="col-12 col-lg-4 d-flex flex-column">
            <div class="card flex-fill d-flex flex-column">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Сообщения</h5>
                    <button type="button" class="btn btn-icon btn-outline-secondary btn-sm" id="chat-refresh-threads" title="Обновить">
                        <i class="ri ri-refresh-line"></i>
                    </button>
                </div>
                <div class="chat-threads flex-fill" id="chat-threads-list">
                    <div class="text-center py-5 chat-empty">Загрузка…</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8 d-flex flex-column">
            <div class="card flex-fill d-flex flex-column">
                <div class="card-header" id="chat-conversation-header">
                    <span class="chat-empty">Выберите переписку слева</span>
                </div>

                <div class="card-body chat-messages flex-fill" id="chat-messages">
                    <div class="text-center py-5 chat-empty">Нет активной переписки</div>
                </div>

                <div class="card-footer" id="chat-composer" hidden>
                    <div class="alert alert-danger d-none" id="chat-send-alert" role="alert"></div>
                    <form id="chat-composer-form" class="d-flex flex-column gap-2" enctype="multipart/form-data">
                        <div class="d-flex gap-2 align-items-end">
                            <textarea class="form-control" id="chat-message-input" name="body" rows="2" placeholder="Написать сообщение…"></textarea>
                            <label class="btn btn-outline-secondary btn-icon mb-0" title="Прикрепить файл">
                                <i class="ri ri-attachment-2"></i>
                                <input type="file" id="chat-attachment-input" name="attachment" class="d-none">
                            </label>
                            <button type="submit" class="btn btn-primary" id="chat-send-button">
                                <span class="spinner-border spinner-border-sm d-none" id="chat-send-spinner"></span>
                                Отправить
                            </button>
                        </div>
                        <div class="small text-muted" id="chat-attachment-name"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var token = (document.cookie.match(/(?:^|; )token=([^;]*)/) || [])[1];
            var headers = { Accept: 'application/json' };
            if (token) {
                headers.Authorization = 'Bearer ' + token;
            }

            var threadsList = document.getElementById('chat-threads-list');
            var conversationHeader = document.getElementById('chat-conversation-header');
            var messagesEl = document.getElementById('chat-messages');
            var composer = document.getElementById('chat-composer');
            var composerForm = document.getElementById('chat-composer-form');
            var messageInput = document.getElementById('chat-message-input');
            var attachmentInput = document.getElementById('chat-attachment-input');
            var attachmentName = document.getElementById('chat-attachment-name');
            var sendButton = document.getElementById('chat-send-button');
            var sendSpinner = document.getElementById('chat-send-spinner');
            var sendAlert = document.getElementById('chat-send-alert');
            var refreshButton = document.getElementById('chat-refresh-threads');

            var state = {
                threads: [],
                activeThreadId: null,
                echo: null,
                channel: null,
            };

            function escapeHtml(value) {
                if (value === null || value === undefined) return '';
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function formatTime(value) {
                if (!value) return '';
                try {
                    return new Intl.DateTimeFormat('ru', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value));
                } catch (e) {
                    return value;
                }
            }

            function renderThreads() {
                if (!state.threads.length) {
                    threadsList.innerHTML = '<div class="text-center py-5 chat-empty">Переписок пока нет</div>';
                    return;
                }

                threadsList.innerHTML = '<div class="list-group list-group-flush">' + state.threads.map(function (thread) {
                    var active = thread.id === state.activeThreadId ? ' active' : '';
                    return '' +
                        '<button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-2 chat-thread-item' + active + '" data-thread-id="' + thread.id + '">' +
                        '<div class="flex-fill">' +
                        '<div class="d-flex align-items-center justify-content-between">' +
                        '<span class="chat-thread-item__name">' + escapeHtml(thread.client_name || 'Клиент') + '</span>' +
                        '<span class="small text-muted">' + formatTime(thread.last_message_at) + '</span>' +
                        '</div>' +
                        '<div class="chat-thread-item__preview">' + escapeHtml(thread.last_message_preview || '') + '</div>' +
                        '</div>' +
                        (thread.unread ? '<span class="chat-thread-item__unread"></span>' : '') +
                        '</button>';
                }).join('') + '</div>';
            }

            function renderMessages(thread) {
                var messages = thread.messages || [];

                if (!messages.length) {
                    messagesEl.innerHTML = '<div class="text-center py-5 chat-empty">Пока нет сообщений</div>';
                    return;
                }

                messagesEl.innerHTML = messages.map(function (message) {
                    var variant = message.sender_type === 'master' ? 'chat-bubble--master' : 'chat-bubble--client';
                    var attachment = message.attachment_url
                        ? '<div><a href="' + escapeHtml(message.attachment_url) + '" target="_blank" rel="noopener">' + escapeHtml(message.attachment_name || 'Вложение') + '</a></div>'
                        : '';

                    return '' +
                        '<div class="chat-bubble ' + variant + '">' +
                        (message.body ? '<div>' + escapeHtml(message.body) + '</div>' : '') +
                        attachment +
                        '<div class="chat-bubble__meta">' + formatTime(message.created_at) + '</div>' +
                        '</div>';
                }).join('');

                messagesEl.scrollTop = messagesEl.scrollHeight;
            }

            function ensureEcho() {
                if (state.echo) return state.echo;
                if (typeof window.Echo !== 'function') return null;

                var root = document.documentElement;
                var key = root.getAttribute('data-pusher-key');
                if (!key) return null;

                var cluster = root.getAttribute('data-pusher-cluster') || undefined;
                var host = root.getAttribute('data-pusher-host') || undefined;
                var port = root.getAttribute('data-pusher-port');
                var scheme = root.getAttribute('data-pusher-scheme') || 'https';

                var options = {
                    broadcaster: 'pusher',
                    key: key,
                    cluster: cluster,
                    forceTLS: scheme !== 'http',
                    encrypted: true,
                    authorizer: function (channel) {
                        return {
                            authorize: function (socketId, callback) {
                                fetch('/broadcasting/auth', {
                                    method: 'POST',
                                    headers: Object.assign({}, headers, { 'Content-Type': 'application/json' }),
                                    body: JSON.stringify({ socket_id: socketId, channel_name: channel.name }),
                                })
                                    .then(function (response) { return response.json(); })
                                    .then(function (data) { callback(false, data); })
                                    .catch(function (error) { callback(true, error); });
                            },
                        };
                    },
                    enabledTransports: ['ws', 'wss'],
                };

                if (host) { options.wsHost = host; options.wssHost = host; }
                if (port) {
                    var parsedPort = parseInt(port, 10);
                    if (!Number.isNaN(parsedPort)) { options.wsPort = parsedPort; options.wssPort = parsedPort; }
                }

                state.echo = new window.Echo(options);
                return state.echo;
            }

            function subscribeToThread(threadId) {
                var echo = ensureEcho();
                if (!echo) return;

                if (state.channel) {
                    echo.leave('chat-thread.' + state.activeThreadId);
                    state.channel = null;
                }

                state.channel = echo.private('chat-thread.' + threadId);
                state.channel.listen('.ChatMessageCreated', function () {
                    // A client message just landed — reload to append it and mark
                    // the thread read server-side, same as opening it fresh would.
                    loadThread(threadId, { silent: true });
                    loadThreads();
                });
            }

            function loadThreads() {
                return fetch('/api/v1/chat/threads', { headers: headers })
                    .then(function (response) { return response.ok ? response.json() : Promise.reject(); })
                    .then(function (payload) {
                        state.threads = payload.data || [];
                        renderThreads();

                        if (!state.activeThreadId && state.threads.length) {
                            loadThread(state.threads[0].id);
                        }
                    })
                    .catch(function () {
                        threadsList.innerHTML = '<div class="text-center py-5 text-danger">Не удалось загрузить переписки</div>';
                    });
            }

            function loadThread(threadId, options) {
                state.activeThreadId = threadId;
                renderThreads();

                return fetch('/api/v1/chat/threads/' + threadId, { headers: headers })
                    .then(function (response) { return response.ok ? response.json() : Promise.reject(); })
                    .then(function (payload) {
                        var thread = payload.data;
                        conversationHeader.innerHTML = '<h6 class="mb-0">' + escapeHtml(thread.client_name || 'Клиент') + '</h6>';
                        composer.hidden = false;
                        renderMessages(thread);
                        subscribeToThread(threadId);

                        if (!(options && options.silent)) {
                            loadThreads();
                        } else {
                            // Clear the unread dot locally without a full re-fetch.
                            state.threads = state.threads.map(function (item) {
                                return item.id === threadId ? Object.assign({}, item, { unread: false }) : item;
                            });
                            renderThreads();
                        }
                    })
                    .catch(function () {
                        messagesEl.innerHTML = '<div class="text-center py-5 text-danger">Не удалось загрузить переписку</div>';
                    });
            }

            threadsList.addEventListener('click', function (event) {
                var button = event.target.closest('[data-thread-id]');
                if (!button) return;
                loadThread(parseInt(button.dataset.threadId, 10));
            });

            refreshButton.addEventListener('click', function () {
                loadThreads();
            });

            attachmentInput.addEventListener('change', function () {
                var file = attachmentInput.files && attachmentInput.files[0];
                attachmentName.textContent = file ? file.name : '';
            });

            composerForm.addEventListener('submit', function (event) {
                event.preventDefault();
                if (!state.activeThreadId) return;

                sendAlert.classList.add('d-none');

                var body = messageInput.value.trim();
                var file = attachmentInput.files && attachmentInput.files[0];
                if (!body && !file) return;

                sendButton.disabled = true;
                sendSpinner.classList.remove('d-none');

                var formData = new FormData();
                if (body) formData.append('body', body);
                if (file) formData.append('attachment', file);

                fetch('/api/v1/chat/threads/' + state.activeThreadId + '/messages', {
                    method: 'POST',
                    headers: headers,
                    body: formData,
                })
                    .then(function (response) { return response.ok ? response.json() : Promise.reject(response); })
                    .then(function (payload) {
                        renderMessages(payload.data);
                        messageInput.value = '';
                        attachmentInput.value = '';
                        attachmentName.textContent = '';
                        loadThreads();
                    })
                    .catch(function () {
                        sendAlert.textContent = 'Не удалось отправить сообщение.';
                        sendAlert.classList.remove('d-none');
                    })
                    .finally(function () {
                        sendButton.disabled = false;
                        sendSpinner.classList.add('d-none');
                    });
            });

            messageInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    composerForm.requestSubmit();
                }
            });

            document.addEventListener('visibilitychange', function () {
                if (document.visibilityState === 'visible') loadThreads();
            });

            setInterval(function () {
                if (document.visibilityState === 'visible') loadThreads();
            }, 30000);

            loadThreads();
        });
    </script>
@endpush

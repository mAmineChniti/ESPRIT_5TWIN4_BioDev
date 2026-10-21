<div id="journey-chat" class="fixed bottom-4 right-4 z-50 sm:bottom-6 sm:right-6">
    <section
        id="journey-chat-panel"
        role="dialog"
        aria-modal="true"
        aria-labelledby="journey-chat-title"
        class="fixed inset-0 z-50 flex h-dvh w-screen flex-col border border-border bg-background shadow-xl sm:inset-auto sm:bottom-24 sm:right-6 sm:h-[min(36rem,calc(100dvh-8rem))] sm:w-[min(24rem,calc(100vw-3rem))] sm:rounded-lg"
        hidden
    >
        <header class="flex items-center justify-between gap-3 rounded-t-lg bg-primary px-4 py-3 text-primary-foreground">
            <h2 id="journey-chat-title" class="font-semibold">{{ __('Journey assistant') }}</h2>
            <button
                id="journey-chat-close"
                type="button"
                class="rounded-md p-2 hover:bg-primary-foreground/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary-foreground"
                aria-label="{{ __('Close assistant') }}"
            >×</button>
        </header>

        <div id="journey-chat-messages" role="log" aria-live="polite" aria-relevant="additions" class="flex-1 space-y-3 overflow-y-auto p-4">
            <p class="max-w-[85%] rounded-lg bg-muted p-3 text-sm text-foreground">{{ __('Hello! Ask me about this journey.') }}</p>
        </div>

        <div id="journey-chat-suggestions" class="flex flex-wrap gap-2 border-t border-border px-3 py-2">
            @foreach ([
                __('Where does this product come from?'),
                __('How many kilometers?'),
                __('Is it organic?'),
            ] as $suggestion)
                <button
                    type="button"
                    class="rounded-md border border-border px-2 py-1 text-xs text-foreground hover:bg-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-ring"
                    data-journey-chat-suggestion="{{ $suggestion }}"
                >{{ $suggestion }}</button>
            @endforeach
        </div>

        <div id="journey-chat-thinking" role="status" aria-live="polite" class="hidden px-4 pb-2">
            <span class="sr-only">{{ __('Thinking…') }}</span>
            <span aria-hidden="true" class="inline-flex gap-1">
                <span class="size-2 animate-bounce rounded-full bg-muted-foreground"></span>
                <span class="size-2 animate-bounce rounded-full bg-muted-foreground [animation-delay:150ms]"></span>
                <span class="size-2 animate-bounce rounded-full bg-muted-foreground [animation-delay:300ms]"></span>
            </span>
        </div>

        <form id="journey-chat-form" class="flex gap-2 border-t border-border p-3" novalidate>
            <april:label for="journey-chat-question" class="sr-only">{{ __('Ask about this journey') }}</april:label>
            <april:input
                id="journey-chat-question"
                name="question"
                type="text"
                maxlength="200"
                autocomplete="off"
                placeholder="{{ __('Type your question…') }}"
                required
                class="min-w-0 flex-1"
            />
            <april:button id="journey-chat-send" type="submit">{{ __('Send') }}</april:button>
        </form>
    </section>

    <button
        id="journey-chat-open"
        type="button"
        class="flex size-14 items-center justify-center rounded-full bg-primary text-2xl text-primary-foreground shadow-lg hover:bg-primary/90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-ring"
        aria-label="{{ __('Open journey assistant') }}"
        aria-controls="journey-chat-panel"
        aria-expanded="false"
    >💬</button>
</div>

@push('scripts')
    <script>
        (() => {
            const panel = document.getElementById('journey-chat-panel');
            const openButton = document.getElementById('journey-chat-open');
            const closeButton = document.getElementById('journey-chat-close');
            const form = document.getElementById('journey-chat-form');
            const input = document.getElementById('journey-chat-question');
            const sendButton = document.getElementById('journey-chat-send');
            const messages = document.getElementById('journey-chat-messages');
            const suggestions = document.getElementById('journey-chat-suggestions');
            const thinking = document.getElementById('journey-chat-thinking');

            if (!panel || !openButton || !closeButton || !form || !input || !sendButton || !messages || !suggestions || !thinking) {
                return;
            }

            const endpoint = @js(route('public.journeys.chat', $journey));
            const locale = @js(app()->getLocale());
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const unavailableMessage = @js(__('The journey assistant is temporarily unavailable. Please try again.'));
            const requiredMessage = @js(__('Please enter a question.'));
            const question = @js(__('You'));
            let isSending = false;

            const closePanel = () => {
                panel.hidden = true;
                openButton.setAttribute('aria-expanded', 'false');
                openButton.focus();
            };

            const appendMessage = (text, isUser, isError = false) => {
                const row = document.createElement('div');
                row.className = `flex ${isUser ? 'justify-end' : 'justify-start'}`;

                const bubble = document.createElement('p');
                bubble.className = isUser
                    ? 'max-w-[85%] rounded-lg bg-primary p-3 text-sm text-primary-foreground'
                    : 'max-w-[85%] rounded-lg bg-muted p-3 text-sm text-foreground';
                if (isError) {
                    bubble.classList.add('border', 'border-destructive', 'text-destructive');
                }
                bubble.textContent = isUser ? `${question}: ${text}` : text;
                row.append(bubble);
                messages.append(row);
                messages.scrollTop = messages.scrollHeight;
            };

            const sendQuestion = async (rawQuestion) => {
                const text = rawQuestion.trim();
                if (!text || isSending) {
                    if (!text) {
                        appendMessage(requiredMessage, false, true);
                    }
                    return;
                }

                isSending = true;
                input.disabled = true;
                sendButton.disabled = true;
                thinking.classList.remove('hidden');
                appendMessage(text, true);

                try {
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ question: text, locale }),
                    });
                    const result = await response.json();

                    if (!response.ok) {
                        throw new Error(result.message || unavailableMessage);
                    }

                    appendMessage(result.answer, false);
                    input.value = '';
                } catch (error) {
                    appendMessage(error instanceof Error ? error.message : unavailableMessage, false, true);
                } finally {
                    isSending = false;
                    input.disabled = false;
                    sendButton.disabled = false;
                    thinking.classList.add('hidden');
                    input.focus();
                }
            };

            openButton.addEventListener('click', () => {
                panel.hidden = false;
                openButton.setAttribute('aria-expanded', 'true');
                input.focus();
            });
            closeButton.addEventListener('click', closePanel);
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !panel.hidden) {
                    closePanel();
                }
            });
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                sendQuestion(input.value);
            });
            suggestions.addEventListener('click', (event) => {
                const button = event.target.closest('[data-journey-chat-suggestion]');
                if (button) {
                    sendQuestion(button.dataset.journeyChatSuggestion);
                }
            });
        })();
    </script>
@endpush

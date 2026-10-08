/**
 * Alpine component backing the product assistant panel.
 *
 * The panel never talks to the model directly: it asks the server, which holds
 * the product record and the API key, and returns the answer plus the record
 * fields it was based on. That keeps the key off the client and means the
 * grounding cannot be bypassed from the browser.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('nutritraceAssistant', (foodId, endpoint, enabled) => ({
        foodId,
        endpoint,
        enabled,
        question: '',
        loading: false,
        error: '',
        suggestions: [],
        messages: [
            {
                id: 'intro',
                role: 'assistant',
                body:
                    'I answer questions about this product using only what NutriTrace has recorded for it. ' +
                    'Ask me anything about its origin, its certifications, or its supply chain.',
                sources: [],
                cannotAnswer: false,
            },
        ],

        init() {
            if (!this.enabled) {
                return;
            }

            this.loadSuggestions();
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        },

        async loadSuggestions() {
            try {
                const response = await fetch(
                    `/products/${this.foodId}/ask/suggestions`,
                    { headers: { Accept: 'application/json' } }
                );

                if (response.ok) {
                    const data = await response.json();

                    this.suggestions = Array.isArray(data.suggestions) ? data.suggestions : [];
                }
            } catch {
                // Suggestions are a convenience; failing to load them is not worth
                // surfacing an error for, the input still works.
            }
        },

        async ask(text) {
            const question = (text ?? this.question).trim();

            if (!question || this.loading || !this.enabled) {
                return;
            }

            this.loading = true;
            this.error = '';
            this.push('user', question);

            try {
                const response = await fetch(this.endpoint, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        // Laravel does not exempt XHR from CSRF, and this
                        // request carries no form to hold the token.
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify({ question }),
                    credentials: 'same-origin',
                });

                const data = await response.json().catch(() => ({}));

                if (data.failure) {
                    this.error = data.failure;

                    return;
                }

                if (!response.ok) {
                    this.error =
                        data.message ||
                        'The assistant could not be reached. Please try again in a moment.';

                    return;
                }

                this.push('assistant', data.answer, data.sources, data.cannot_answer);
            } catch {
                this.error = 'The assistant could not be reached. Please try again in a moment.';
            } finally {
                this.loading = false;
                this.question = '';
            }
        },

        push(role, body, sources = [], cannotAnswer = false) {
            this.messages.push({
                id: `${role}-${Date.now()}-${this.messages.length}`,
                role,
                body: body ?? '',
                sources: Array.isArray(sources) ? sources : [],
                cannotAnswer: Boolean(cannotAnswer),
            });
        },
    }));
});
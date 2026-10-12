{{--
    The product assistant: a chat panel grounded in the product's traceability
    record.

    Every reply has to name the record fields it used, and the panel shows them,
    so the consumer can check the answer instead of taking it on trust.
--}}
@props(['food', 'enabled' => true])

<div class="space-y-4"
     x-data="nutritraceAssistant(@js($food->id), @js(route('products.ask', $food)), @js($enabled), @js(__('I answer questions about this product using only what NutriTrace has recorded for it. Ask me anything about its origin, its certifications, or its supply chain.')), @js(__('The assistant could not be reached. Please try again in a moment.')))">

    <april:alert variant="none" class="border-primary/30 bg-primary/5" x-show="! enabled" x-cloak>
        <x-slot:icon><x-lucide-key-round class="size-4" /></x-slot:icon>
        <x-slot:title>{{ __('Assistant not configured') }}</x-slot:title>
        <x-slot:description>
            {!! __('The assistant needs an AI provider key. Set :key in your :env file to switch it on.', ['key' => '<code class="font-mono">AI_KEY</code>', 'env' => '<code class="font-mono">.env</code>']) !!}
        </x-slot:description>
    </april:alert>

    <ol class="max-h-96 space-y-3 overflow-y-auto pr-1" aria-live="polite" aria-label="{{ __('Conversation') }}">
        <template x-for="message in messages" :key="message.id">
            <li class="flex" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
                <div class="max-w-[85%] rounded-lg px-3.5 py-2.5 text-sm"
                     :class="message.role === 'user'
                        ? 'bg-primary text-primary-foreground'
                        : 'border border-border bg-muted/50 text-foreground'">
                    <p x-text="message.body"></p>

                    {{-- What the answer was based on. --}}
                    <div x-show="message.role === 'assistant' && message.sources.length" class="mt-2">
                        <p class="text-xs font-medium opacity-80">{{ __('Based on') }}</p>
                        <ul class="mt-1 flex flex-wrap gap-1">
                            <template x-for="source in message.sources" :key="source">
                                <li class="rounded-md bg-background/70 px-1.5 py-0.5 font-mono text-xs text-muted-foreground"
                                    x-text="source"></li>
                            </template>
                        </ul>
                    </div>

                    <p x-show="message.role === 'assistant' && message.cannot_answer"
                       class="mt-2 text-xs italic opacity-80">
                        {{ __('The record does not contain this, so no answer was invented.') }}
                    </p>
                </div>
            </li>
        </template>

        <li x-show="loading" class="flex justify-start" x-cloak>
            <april:loading-spinner size="sm" />
            <span class="ml-2 text-sm text-muted-foreground">{{ __('Reading the product record…') }}</span>
        </li>

        <li x-show="error" class="rounded-lg border border-destructive/40 bg-destructive/5 p-3 text-sm text-destructive" x-cloak>
            <p x-text="error"></p>
        </li>
    </ol>

    <div class="flex flex-wrap gap-1.5" x-show="suggestions.length" x-cloak>
        <template x-for="suggestion in suggestions" :key="suggestion">
            <button type="button"
                    class="rounded-full border border-border px-2.5 py-1 text-xs text-muted-foreground transition hover:border-primary/50 hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    x-text="suggestion"
                    @click="ask(suggestion)"></button>
        </template>
    </div>

    <form class="flex items-end gap-2" @submit.prevent="ask(question); question = ''" novalidate>
        <div class="flex-1">
            <april:label for="assistant-question-{{ $food->id }}" class="sr-only">
                {{ __('Ask a question about :name', ['name' => $food->name]) }}
            </april:label>
            <x-textarea-field id="assistant-question-{{ $food->id }}"
                x-model="question"
                rows="2"
                maxlength="500"
                placeholder="{{ __('Is this really organic? Where was it grown?') }}"
                x-bind:disabled="! enabled" />
        </div>
        <april:button type="submit" x-bind:disabled="! enabled || loading || ! question.trim()">
            <x-lucide-send class="size-4" />
            <span class="sr-only">{{ __('Ask') }}</span>
        </april:button>
    </form>

    <p class="text-xs text-muted-foreground">
        {{ __('The assistant only reads what NutriTrace has recorded for this product. If the record does not answer your question, it will say so instead of guessing.') }}
    </p>
</div>

@once
    @push('scripts')
        @vite(['resources/js/assistant.js'])
    @endpush
@endonce
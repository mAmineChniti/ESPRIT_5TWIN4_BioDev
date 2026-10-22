{{--
    The AI transport advisor for the shipment form.

    It reads the load typed into the form, asks the server for a recommendation
    and shows it next to the footprint of every mode. Nothing is saved: the
    distributor applies the suggestion to the "Transport mode" field, or ignores
    it. The footprints come from the server's calculator; only the choice and
    its explanation come from the model.
--}}
<div class="rounded-lg border border-border bg-muted/30 p-4 space-y-3"
     x-data="{
        endpoint: @js(route('logistics.shipments.advise')),
        unreachable: @js(__('The advisor could not be reached. Please try again in a moment.')),
        loading: false,
        error: '',
        advice: null,

        field(id) {
            return document.getElementById(id)?.value ?? '';
        },

        async ask() {
            if (this.loading) {
                return;
            }

            this.loading = true;
            this.error = '';
            this.advice = null;

            try {
                const response = await fetch(this.endpoint, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        destination: this.field('destination'),
                        distance_km: this.field('distance_km'),
                        weight_kg: this.field('weight_kg'),
                        warehouse_id: this.field('warehouse_id'),
                        food_id: this.field('food_id'),
                        transport_mode: this.field('transport_mode'),
                    }),
                });

                const data = await response.json().catch(() => ({}));

                if (response.status === 422) {
                    this.error = Object.values(data.errors ?? {})[0]?.[0] ?? this.unreachable;

                    return;
                }

                if (!response.ok) {
                    this.error = this.unreachable;

                    return;
                }

                this.advice = data;
            } catch {
                this.error = this.unreachable;
            } finally {
                this.loading = false;
            }
        },

        apply() {
            const select = document.getElementById('transport_mode');

            if (!select || !this.advice) {
                return;
            }

            select.value = this.advice.mode;
            select.dispatchEvent(new Event('change', { bubbles: true }));
        },
     }">

    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <h2 class="flex items-center gap-2 text-sm font-semibold">
                <x-lucide-sparkles class="size-4 text-primary" />
                {{ __('Transport advisor') }}
            </h2>
            <p class="text-xs text-muted-foreground">{{ __('Fill in the distance and weight, then ask for the lowest-footprint mode that suits the load.') }}</p>
        </div>
        <april:button type="button" variant="outline" size="sm" x-on:click="ask()" x-bind:disabled="loading">
            <span x-text="loading ? @js(__('Analysing…')) : @js(__('Ask the advisor'))">{{ __('Ask the advisor') }}</span>
        </april:button>
    </div>

    <template x-if="error">
        <p class="text-sm text-destructive" role="alert" x-text="error"></p>
    </template>

    <template x-if="advice">
        <div class="space-y-3" aria-live="polite">
            <div class="rounded-md border border-primary/30 bg-primary/5 p-3 text-sm space-y-2">
                <p class="font-medium">
                    {{ __('Suggested mode') }}:
                    <span x-text="advice.mode_label"></span>
                    <span class="font-normal text-muted-foreground">
                        (<span x-text="advice.recommended_text"></span> kg CO₂e)
                    </span>
                </p>
                <p x-text="advice.reason"></p>
                <p x-show="advice.saving_text" x-text="advice.saving_text" class="text-muted-foreground"></p>
                <p class="text-xs text-muted-foreground"
                   x-text="advice.source === 'ai' ? @js(__('Suggested by the AI model. Footprints are calculated by NutriTrace.')) : @js(__('AI unavailable: suggested by a distance rule. Footprints are calculated by NutriTrace.'))"></p>
                <april:button type="button" size="sm" x-on:click="apply()">
                    <x-lucide-check class="size-4" />
                    {{ __('Use this mode') }}
                </april:button>
            </div>

            <table class="w-full text-left text-sm">
                <caption class="sr-only">{{ __('Carbon footprint of the same load by transport mode') }}</caption>
                <tbody class="divide-y divide-border">
                    <template x-for="row in advice.comparison" :key="row.mode">
                        <tr :class="row.recommended ? 'font-semibold' : ''">
                            <td class="py-1.5">
                                <span x-text="row.label"></span>
                                <span x-show="row.recommended" class="text-xs text-primary">{{ __('(suggested)') }}</span>
                                <span x-show="row.current" class="text-xs text-muted-foreground">{{ __('(current)') }}</span>
                            </td>
                            <td class="py-1.5 text-right"><span x-text="row.kg_text"></span> kg</td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </template>
</div>
{{--
    The AI carbon analysis of a warehouse.

    The server computes every figure from the recorded shipments; the AI model
    only writes the summary and the recommendations around them. Nothing is
    saved: the analysis is produced on demand.
--}}
@props(['warehouse'])

<div class="max-w-2xl mb-6 rounded-lg border border-border bg-card p-6 space-y-4"
     x-data="{
        endpoint: @js(route('logistics.warehouses.analyze', $warehouse)),
        unreachable: @js(__('The analysis could not be reached. Please try again in a moment.')),
        loading: false,
        error: '',
        empty: '',
        report: null,

        async analyse() {
            if (this.loading) {
                return;
            }

            this.loading = true;
            this.error = '';
            this.empty = '';
            this.report = null;

            try {
                const response = await fetch(this.endpoint, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                    },
                    credentials: 'same-origin',
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    this.error = this.unreachable;

                    return;
                }

                if (data.empty) {
                    this.empty = data.message;

                    return;
                }

                this.report = data;
            } catch {
                this.error = this.unreachable;
            } finally {
                this.loading = false;
            }
        },
     }">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="flex items-center gap-2 text-lg font-semibold">
                <x-lucide-sparkles class="size-5 text-primary" />
                {{ __('AI carbon analysis') }}
            </h2>
            <p class="text-sm text-muted-foreground">{{ __('A written summary of the carbon footprint of the shipments leaving this warehouse, with ways to reduce it.') }}</p>
        </div>
        <april:button type="button" variant="outline" size="sm" x-on:click="analyse()" x-bind:disabled="loading">
            <span x-text="loading ? @js(__('Analysing…')) : @js(__('Analyse with AI'))">{{ __('Analyse with AI') }}</span>
        </april:button>
    </div>

    <template x-if="error">
        <p class="text-sm text-destructive" role="alert" x-text="error"></p>
    </template>

    <template x-if="empty">
        <p class="text-sm text-muted-foreground" x-text="empty"></p>
    </template>

    <template x-if="report">
        <div class="space-y-5" aria-live="polite">
            <dl class="grid grid-cols-3 gap-4 text-sm">
                <div>
                    <dt class="text-muted-foreground">{{ __('Shipments') }}</dt>
                    <dd class="text-xl font-bold tabular-nums" x-text="report.shipments_count"></dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">{{ __('Total footprint') }}</dt>
                    <dd class="text-xl font-bold tabular-nums"><span x-text="report.total_text"></span> <span class="text-sm font-normal text-muted-foreground">kg CO₂e</span></dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">{{ __('Average per shipment') }}</dt>
                    <dd class="text-xl font-bold tabular-nums"><span x-text="report.average_text"></span> <span class="text-sm font-normal text-muted-foreground">kg</span></dd>
                </div>
            </dl>

            <div class="rounded-md border border-primary/30 bg-primary/5 p-3 text-sm space-y-3">
                <div>
                    <h3 class="font-medium">{{ __('Summary') }}</h3>
                    <p x-text="report.summary"></p>
                </div>
                <div>
                    <h3 class="font-medium">{{ __('Ways to reduce the footprint') }}</h3>
                    <ul class="list-disc space-y-1 ps-5">
                        <template x-for="item in report.recommendations" :key="item">
                            <li x-text="item"></li>
                        </template>
                    </ul>
                </div>
                <p x-show="report.saving_kg > 0" class="text-muted-foreground">
                    {{ __('Indicative saving if road and air shipments moved to freight rail') }}:
                    <span class="font-medium" x-text="report.saving_text"></span> kg CO₂e
                </p>
            </div>

            <div>
                <h3 class="mb-2 text-sm font-medium">{{ __('Footprint by transport mode') }}</h3>
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">{{ __('Footprint by transport mode') }}</caption>
                    <thead class="text-xs uppercase text-muted-foreground">
                        <tr>
                            <th scope="col" class="py-1">{{ __('Mode') }}</th>
                            <th scope="col" class="py-1 text-right">{{ __('Shipments') }}</th>
                            <th scope="col" class="py-1 text-right">{{ __('CO₂ (kg)') }}</th>
                            <th scope="col" class="py-1 text-right">{{ __('Share') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <template x-for="row in report.by_mode" :key="row.mode">
                            <tr>
                                <td class="py-1.5">
                                    <span x-text="row.label"></span>
                                    <div class="mt-1 h-1.5 w-full rounded bg-muted">
                                        <div class="h-1.5 rounded bg-primary" x-bind:style="{ width: row.share + '%' }"></div>
                                    </div>
                                </td>
                                <td class="py-1.5 text-right tabular-nums" x-text="row.count"></td>
                                <td class="py-1.5 text-right tabular-nums" x-text="row.kg_text"></td>
                                <td class="py-1.5 text-right tabular-nums"><span x-text="row.share_text"></span>%</td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div>
                <h3 class="mb-2 text-sm font-medium">{{ __('Highest-footprint shipments') }}</h3>
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">{{ __('Highest-footprint shipments') }}</caption>
                    <tbody class="divide-y divide-border">
                        <template x-for="row in report.top" :key="row.reference">
                            <tr>
                                <td class="py-1.5 font-medium" x-text="row.reference"></td>
                                <td class="py-1.5 text-muted-foreground" x-text="row.destination"></td>
                                <td class="py-1.5 text-muted-foreground" x-text="row.mode_label"></td>
                                <td class="py-1.5 text-right tabular-nums"><span x-text="row.kg_text"></span> kg</td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <p class="text-xs text-muted-foreground"
               x-text="report.source === 'ai' ? @js(__('Written by the AI model. Every figure is calculated by NutriTrace from the recorded shipments.')) : @js(__('AI unavailable: written from fixed rules. Every figure is calculated by NutriTrace from the recorded shipments.'))"></p>
        </div>
    </template>
</div>
{{--
    Light / dark / system picker.

    Reuses April's dropdown menu so the open/close behaviour and focus handling
    match the rest of the design system. Reads the persisted mode from the
    inline bootstrap script so the highlighted item is correct on first paint.

    The preference lives in localStorage, so it follows the browser rather than
    the account. Nothing server side is involved.
--}}
{{--
    The wrapper owns the `mode` state. April's dropdown component already
    declares its own x-data, so adding a second x-data to the same element would
    be a duplicate attribute: the parser keeps the first and drops ours, leaving
    `mode` undefined. Alpine scope cascades into the component instead.

    The wrapper is also the positioning context. April's panel is normally placed
    with x-anchor, which needs the Alpine Floating UI plugin — not bundled here —
    so with x-teleport the panel would fall to the end of <body> and render under
    the footer. Anchoring it in CSS keeps it next to the trigger with no extra
    dependency.
--}}
<div class="relative" x-data="{ mode: document.documentElement.dataset.themeMode || 'system' }">
    <april:dropdown-menu>
        <x-slot:trigger>
            <button type="button"
                    class="inline-flex size-9 items-center justify-center rounded-md border border-border bg-card text-muted-foreground transition hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    aria-label="Change colour theme">
                <template x-if="mode === 'light'">
                    <x-lucide-sun class="size-4" />
                </template>
                <template x-if="mode === 'dark'">
                    <x-lucide-moon class="size-4" />
                </template>
                <template x-if="mode === 'system'">
                    <x-lucide-monitor class="size-4" />
                </template>
            </button>
        </x-slot:trigger>

        <x-slot:content class="absolute right-0 top-full z-50 mt-2 min-w-[11rem]">
            <april:dropdown-menu-label>Theme</april:dropdown-menu-label>

            <april:dropdown-menu-item
                x-on:click="NutriTraceTheme.set('light'); mode = 'light'"
                x-bind:class="mode === 'light' && 'bg-accent text-accent-foreground'">
                <span class="flex w-full items-center gap-2">
                    <x-lucide-sun class="size-4" />
                    <span>Light</span>
                    <x-lucide-check class="ml-auto size-4" x-show="mode === 'light'" />
                </span>
            </april:dropdown-menu-item>

            <april:dropdown-menu-item
                x-on:click="NutriTraceTheme.set('dark'); mode = 'dark'"
                x-bind:class="mode === 'dark' && 'bg-accent text-accent-foreground'">
                <span class="flex w-full items-center gap-2">
                    <x-lucide-moon class="size-4" />
                    <span>Dark</span>
                    <x-lucide-check class="ml-auto size-4" x-show="mode === 'dark'" />
                </span>
            </april:dropdown-menu-item>

            <april:dropdown-menu-separator />

            <april:dropdown-menu-item
                x-on:click="NutriTraceTheme.set('system'); mode = 'system'"
                x-bind:class="mode === 'system' && 'bg-accent text-accent-foreground'">
                <span class="flex w-full items-center gap-2">
                    <x-lucide-monitor class="size-4" />
                    <span>System</span>
                    <x-lucide-check class="ml-auto size-4" x-show="mode === 'system'" />
                </span>
            </april:dropdown-menu-item>
        </x-slot:content>
    </april:dropdown-menu>
</div>
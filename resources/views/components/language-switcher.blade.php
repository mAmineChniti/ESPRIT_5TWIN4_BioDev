{{--
    The language switcher: the same page under every locale.

    Uses April's dropdown-menu so open/close behaviour and focus handling
    match the rest of the design system. The current language renders as the
    selected item; choosing a new locale redirects the page via the URL prefix.

    The wrapper owns the state. Alpine scope cascades into the component.
--}}
@php
    $current = \App\Enums\Locale::resolve(app()->getLocale())->value;
@endphp
<div class="relative" x-data="{
    current: '{{ $current }}',
    switchTo(locale) {
        const parts = window.location.pathname.split('/');
        parts[1] = locale;
        window.location = parts.join('/') + window.location.search + window.location.hash;
    }
}">
    <april:dropdown-menu>
        <x-slot:trigger>
            <april:button variant="outline" size="icon" aria-label="{{ __('Change language') }}">
                <span class="sr-only">{{ __('Current language') }}</span>
                <span class="text-xs font-semibold uppercase truncate">{{ app()->getLocale() }}</span>
            </april:button>
        </x-slot:trigger>

        <x-slot:content class="absolute right-0 top-full z-50 mt-2 min-w-[11rem]">
            <april:dropdown-menu-label>{{ __('Language') }}</april:dropdown-menu-label>

            <april:dropdown-menu-item
                x-on:click="switchTo('en')"
                x-bind:class="current === 'en' && 'bg-accent text-accent-foreground'">
                <span class="flex w-full items-center gap-2">
                    <span aria-hidden="true">🇬🇧</span>
                    <span>English</span>
                    <x-lucide-check class="ml-auto size-4" x-show="current === 'en'" />
                </span>
            </april:dropdown-menu-item>

            <april:dropdown-menu-item
                x-on:click="switchTo('fr')"
                x-bind:class="current === 'fr' && 'bg-accent text-accent-foreground'">
                <span class="flex w-full items-center gap-2">
                    <span aria-hidden="true">🇫🇷</span>
                    <span>Français</span>
                    <x-lucide-check class="ml-auto size-4" x-show="current === 'fr'" />
                </span>
            </april:dropdown-menu-item>

            <april:dropdown-menu-item
                x-on:click="switchTo('ar')"
                x-bind:class="current === 'ar' && 'bg-accent text-accent-foreground'">
                <span class="flex w-full items-center gap-2">
                    <span aria-hidden="true">🇸🇦</span>
                    <span>العربية</span>
                    <x-lucide-check class="ml-auto size-4" x-show="current === 'ar'" />
                </span>
            </april:dropdown-menu-item>
        </x-slot:content>
    </april:dropdown-menu>
</div>

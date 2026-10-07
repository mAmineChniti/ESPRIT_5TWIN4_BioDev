{{-- NutriTrace brand logo: sprout mark + two-tone wordmark. --}}
<a {{ $attributes->merge(['href' => url('/'), 'class' => 'inline-flex items-center gap-2.5']) }}>
    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary text-primary-foreground">
        <x-lucide-sprout class="h-5 w-5" stroke-width="2.25" />
    </span>
    <span class="text-lg font-extrabold tracking-tight text-foreground">Nutri<span class="text-primary">Trace</span></span>
</a>

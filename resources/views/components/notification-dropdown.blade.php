@props([
    'drop' => 'down',
    'variant' => 'ghost',
])

@auth
@php
    $user = Auth::user();
    $unreadCount = $user->unreadNotifications->count();
    $notifications = $user->notifications()->latest()->limit(15)->get();
@endphp

<div class="relative inline-flex" x-data="{
    open: false,
    drop: '{{ $drop }}',
    coords: { top: 0, bottom: 0, left: 0, right: 0 },
    toggle() {
        if (!this.open) {
            const btn = this.$refs.trigger;
            if (btn) {
                const r = btn.getBoundingClientRect();
                if (this.drop === 'up') {
                    this.coords.bottom = Math.round(window.innerHeight - r.top + 8);
                    this.coords.left = Math.round(Math.max(16, Math.min(r.left, window.innerWidth - 384 - 16)));
                } else {
                    this.coords.top = Math.round(r.bottom + 8);
                    this.coords.right = Math.round(Math.max(16, window.innerWidth - r.right));
                }
            }
        }
        this.open = !this.open;
    }
}">
    <april:button
        type="button"
        variant="{{ $variant }}"
        size="icon"
        x-ref="trigger"
        @click="toggle()"
        aria-label="{{ __('Notifications') }}"
        title="{{ __('Notifications') }}"
        x-bind:aria-expanded="open"
        class="relative"
    >
        <x-lucide-bell class="size-4" />
        @if($unreadCount > 0)
            <span class="absolute -top-1 -right-1 flex size-4 items-center justify-center rounded-full bg-destructive text-[9px] font-bold text-destructive-foreground animate-pulse shadow-sm">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </april:button>

    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            @click.outside="open = false"
            @keydown.escape.window="open = false"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="fixed z-50 w-80 sm:w-96 rounded-lg border border-border bg-card p-4 shadow-xl max-w-[calc(100vw-2rem)]"
            x-bind:style="drop === 'up'
                ? `bottom: ${coords.bottom}px; left: ${coords.left}px;`
                : `top: ${coords.top}px; right: ${coords.right}px;`"
        >
            <div class="flex items-center justify-between border-b border-border pb-3">
                <div class="flex items-center gap-2">
                    <x-lucide-bell class="size-4 text-primary" />
                    <h3 class="text-sm font-bold text-foreground">{{ __('Notifications') }}</h3>
                    @if($unreadCount > 0)
                        <span class="rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-bold text-primary">
                            {{ $unreadCount }} {{ __('new') }}
                        </span>
                    @endif
                </div>

                @if($unreadCount > 0)
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <button type="submit" class="text-xs text-muted-foreground hover:text-primary transition-colors cursor-pointer">
                            {{ __('Mark all read') }}
                        </button>
                    </form>
                @endif
            </div>

            <div class="mt-3 max-h-80 space-y-2.5 overflow-y-auto pr-1">
                @forelse($notifications as $notif)
                    @php
                        $data = $notif->data;
                        $type = $data['type'] ?? 'info';
                        $isUnread = $notif->unread();
                    @endphp

                    <form method="POST" action="{{ route('notifications.read', $notif->id) }}">
                        @csrf
                        @method('PATCH')
                        <button
                            type="submit"
                            class="w-full text-left rounded-lg border p-3 text-xs transition-colors relative block cursor-pointer {{ $isUnread ? 'bg-accent/40 border-primary/30' : 'bg-card border-border hover:bg-accent/20' }}"
                        >
                            @if($isUnread)
                                <span class="absolute top-3 right-3 size-2 rounded-full bg-primary"></span>
                            @endif

                            @if($type === 'farm_rejected')
                                <div class="flex items-start gap-2">
                                    <x-lucide-x-circle class="size-4 text-destructive shrink-0 mt-0.5" />
                                    <div class="space-y-1 w-full">
                                        <div class="flex items-center justify-between pr-3">
                                            <span class="font-bold text-destructive">{{ $data['title'] ?? __('Farm Rejected') }}</span>
                                        </div>
                                        <p class="text-muted-foreground">{{ $data['message'] ?? '' }}</p>

                                        @if(!empty($data['rejection_reason']))
                                            <div class="rounded bg-background/90 p-2 border border-destructive/20 mt-1">
                                                <span class="block font-semibold text-destructive text-[11px]">{{ __('Reason for rejection:') }}</span>
                                                <p class="text-foreground italic mt-0.5">“{{ $data['rejection_reason'] }}”</p>
                                            </div>
                                        @endif

                                        <span class="block text-[10px] text-muted-foreground pt-1">{{ $notif->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            @elseif($type === 'farm_approved')
                                <div class="flex items-start gap-2">
                                    <x-lucide-check-circle-2 class="size-4 text-primary shrink-0 mt-0.5" />
                                    <div class="space-y-1 w-full">
                                        <div class="flex items-center justify-between pr-3">
                                            <span class="font-bold text-primary">{{ $data['title'] ?? __('Farm Approved') }}</span>
                                        </div>
                                        <p class="text-muted-foreground">{{ $data['message'] ?? '' }}</p>
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-primary pt-0.5">
                                            <x-lucide-file-text class="size-3" />
                                            {{ __('View Certificate') }} →
                                        </span>
                                        <span class="block text-[10px] text-muted-foreground pt-1">{{ $notif->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            @elseif($type === 'farm_pending')
                                <div class="flex items-start gap-2">
                                    <x-lucide-clock class="size-4 text-secondary-foreground shrink-0 mt-0.5" />
                                    <div class="space-y-1 w-full">
                                        <div class="flex items-center justify-between pr-3">
                                            <span class="font-bold text-foreground">{{ $data['title'] ?? __('Farm Pending Validation') }}</span>
                                        </div>
                                        <p class="text-muted-foreground">{{ $data['message'] ?? '' }}</p>
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-primary pt-0.5">
                                            {{ __('Review request') }} →
                                        </span>
                                        <span class="block text-[10px] text-muted-foreground pt-1">{{ $notif->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            @else
                                <div class="space-y-1">
                                    <span class="font-bold text-foreground">{{ $data['title'] ?? __('Notification') }}</span>
                                    <p class="text-muted-foreground">{{ $data['message'] ?? '' }}</p>
                                    <span class="block text-[10px] text-muted-foreground">{{ $notif->created_at->diffForHumans() }}</span>
                                </div>
                            @endif
                        </button>
                    </form>
                @empty
                    <div class="py-6 text-center text-xs text-muted-foreground">
                        <x-lucide-bell-off class="mx-auto size-6 opacity-40 mb-1" />
                        {{ __('No notifications at this time.') }}
                    </div>
                @endforelse
            </div>
        </div>
    </template>
</div>
@endauth

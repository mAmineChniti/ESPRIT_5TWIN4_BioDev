{{-- Moved inside the unverified block: rendered unconditionally it shipped an
     empty CSRF form on every page load with no possible submit button. --}}
@if (! $user->hasVerifiedEmail())
    <form id="send-verification" method="post" action="{{ route('verification.send') }}" novalidate>
        @csrf
    </form>
@endif

<form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-4" novalidate>
    @csrf
    @method('patch')

    <div class="flex items-center gap-4">
        <april:avatar size="lg">
            @if ($user->avatarUrl())
                <x-slot:image src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}"></x-slot:image>
            @endif
            <x-slot:fallback class="bg-primary text-base font-bold text-primary-foreground">{{ strtoupper(substr($user->name, 0, 1)) }}</x-slot:fallback>
        </april:avatar>

        <div class="flex-1 space-y-2">
            <april:label for="avatar">{{ __('Profile photo') }}</april:label>
            <april:input id="avatar" name="avatar" type="file" accept="image/png,image/jpeg,image/webp"
                         aria-describedby="avatar-error"
                         :aria-invalid="$errors->has('avatar') ? 'true' : 'false'" />
            <x-input-error id="avatar-error" :messages="$errors->get('avatar')" />

            @if ($user->avatar_path)
                <div class="flex items-center gap-2 pt-1">
                    <april:checkbox id="remove_avatar" name="remove_avatar" value="1" />
                    <april:label for="remove_avatar" class="text-xs font-normal text-muted-foreground">{{ __('Remove current photo') }}</april:label>
                </div>
            @endif
        </div>
    </div>

    <div class="space-y-2">
        <april:label for="name">{{ __("Name") }}</april:label>
        <april:input id="name" name="name" type="text" :value="old('name', $user->name)" required autofocus
                     autocomplete="name" aria-describedby="name-error"
                     :aria-invalid="$errors->has('name') ? 'true' : 'false'" />
        <x-input-error id="name-error" :messages="$errors->get('name')" />
    </div>

    <div class="space-y-2">
        <april:label for="email">{{ __("Email") }}</april:label>
        <april:input id="email" name="email" type="email" :value="old('email', $user->email)" required
                     autocomplete="username" aria-describedby="email-error"
                     :aria-invalid="$errors->has('email') ? 'true' : 'false'" />
        <x-input-error id="email-error" :messages="$errors->get('email')" />

        @if (! $user->hasVerifiedEmail())
            <p class="text-sm text-muted-foreground">
                {{ __('Your email address is unverified.') }}
                <april:button type="submit" form="send-verification" variant="link" size="sm" class="px-1">{{ __("Re-send verification email") }}</april:button>
            </p>

            @if (session('status') === 'verification-link-sent')
                <p class="text-sm font-medium text-primary" role="status" aria-live="polite">
                    {{ __('A new verification link has been sent to your email address.') }}
                </p>
            @endif
        @endif
    </div>

    <div class="flex items-center gap-4 pt-1">
        <april:button type="submit">{{ __("Save") }}</april:button>

        @if (session('status') === 'profile-updated')
            <p
                x-data="{ show: true }"
                x-show="show"
                x-transition
                x-init="setTimeout(() => show = false, 2000)"
                class="text-sm text-primary"
                role="status"
                aria-live="polite"
            >{{ __('Saved.') }}</p>
        @endif
    </div>
</form>
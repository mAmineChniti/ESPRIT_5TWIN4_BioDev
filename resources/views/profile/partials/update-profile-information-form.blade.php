<form id="send-verification" method="post" action="{{ route('verification.send') }}">
    @csrf
</form>

<form method="post" action="{{ route('profile.update') }}" class="space-y-4">
    @csrf
    @method('patch')

    <div class="space-y-2">
        <april:label for="name">Name</april:label>
        <april:input id="name" name="name" type="text" :value="old('name', $user->name)" required autofocus autocomplete="name" />
        <x-input-error :messages="$errors->get('name')" />
    </div>

    <div class="space-y-2">
        <april:label for="email">Email</april:label>
        <april:input id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="username" />
        <x-input-error :messages="$errors->get('email')" />

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <p class="text-sm text-muted-foreground">
                Your email address is unverified.
                <april:button type="submit" form="send-verification" variant="link" size="sm" class="px-1">Re-send verification email.</april:button>
            </p>

            @if (session('status') === 'verification-link-sent')
                <p class="text-sm font-medium text-primary">A new verification link has been sent to your email address.</p>
            @endif
        @endif
    </div>

    <div class="flex items-center gap-4 pt-1">
        <april:button type="submit">Save</april:button>

        @if (session('status') === 'profile-updated')
            <p
                x-data="{ show: true }"
                x-show="show"
                x-transition
                x-init="setTimeout(() => show = false, 2000)"
                class="text-sm text-primary"
            >Saved.</p>
        @endif
    </div>
</form>

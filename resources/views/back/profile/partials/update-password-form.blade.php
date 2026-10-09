<form method="post" action="{{ route('password.update') }}" class="space-y-4" novalidate>
    @csrf
    @method('put')

    <div class="space-y-2">
        <april:label for="update_password_current_password">Current password</april:label>
        <april:input id="update_password_current_password" name="current_password" type="password"
                     autocomplete="current-password" aria-describedby="current_password-error" />
        <x-input-error id="current_password-error" :messages="$errors->updatePassword->get('current_password')" />
    </div>

    <div class="space-y-2">
        <april:label for="update_password_password">New password</april:label>
        <april:input id="update_password_password" name="password" type="password"
                     autocomplete="new-password" aria-describedby="new_password-error" />
        <x-input-error id="new_password-error" :messages="$errors->updatePassword->get('password')" />
    </div>

    <div class="space-y-2">
        <april:label for="update_password_password_confirmation">Confirm password</april:label>
        <april:input id="update_password_password_confirmation" name="password_confirmation" type="password"
                     autocomplete="new-password" aria-describedby="password_confirmation-error" />
        <x-input-error id="password_confirmation-error" :messages="$errors->updatePassword->get('password_confirmation')" />
    </div>

    <div class="flex items-center gap-4 pt-1">
        <april:button type="submit">Save</april:button>

        @if (session('status') === 'password-updated')
            <p
                x-data="{ show: true }"
                x-show="show"
                x-transition
                x-init="setTimeout(() => show = false, 2000)"
                class="text-sm text-primary"
                role="status"
                aria-live="polite"
            >Saved.</p>
        @endif
    </div>
</form>

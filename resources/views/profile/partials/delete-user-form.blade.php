<div class="rounded-xl border border-destructive/40 bg-destructive/5 p-4">
    <p class="text-sm text-muted-foreground">
        Once your account is deleted, all of its resources and data will be
        permanently deleted. Enter your password below to confirm.
    </p>

    <form method="post" action="{{ route('profile.destroy') }}" class="mt-4 space-y-4">
        @csrf
        @method('delete')

        <div class="max-w-sm space-y-2">
            <april:label for="password">Password</april:label>
            <april:input id="password" name="password" type="password" placeholder="••••••••" />
            <x-input-error :messages="$errors->userDeletion->get('password')" />
        </div>

        <april:button type="submit" variant="destructive">Delete account</april:button>
    </form>
</div>

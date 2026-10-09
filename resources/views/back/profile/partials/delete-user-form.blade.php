{{-- rounded-lg, not rounded-xl: this panel is nested inside an april:card,
     which is rounded-lg, and an inner corner larger than its container reads
     as a rendering fault. --}}
<div class="rounded-lg border border-destructive/50 bg-destructive/5 p-4">
    <p class="text-sm text-muted-foreground">
        Once your account is deleted, all of its resources and data will be
        permanently deleted. Enter your password below to confirm.
    </p>

    <form method="post" action="{{ route('profile.destroy') }}" class="mt-4 space-y-4">
        @csrf
        @method('delete')

        <div class="max-w-sm space-y-2">
            <april:label for="password">Password</april:label>
            <april:input id="password" name="password" type="password" autocomplete="current-password"
                         placeholder="••••••••"
                         aria-describedby="password-error"
                         @error('userDeletion.password') aria-invalid="true" @enderror />
            <x-input-error id="password-error" :messages="$errors->userDeletion->get('password')" />
        </div>

        <april:alert-dialog>
            <x-slot:trigger>
                <april:button type="button" variant="destructive">Delete account</april:button>
            </x-slot:trigger>
            <x-slot:content>
                <div>
                    <h2 class="text-lg font-semibold" x-bind="title">Delete your account?</h2>
                    <p class="mt-2 text-sm text-muted-foreground" x-bind="description">
                        Your products, reviews, meals and recorded supply chain steps are permanently removed.
                        This cannot be undone.
                    </p>
                </div>
                <april:alert-dialog-footer>
                    <april:alert-dialog-cancel>Cancel</april:alert-dialog-cancel>
                    {{-- Plain submit: the dialog is nested inside the form above,
                         so this posts the ancestor form and therefore carries the
                         password field the endpoint validates against. Pointing
                         it at a separate hidden form would drop that value. --}}
                    <april:button type="submit" variant="destructive" x-bind="action">
                        Delete account
                    </april:button>
                </april:alert-dialog-footer>
            </x-slot:content>
        </april:alert-dialog>
    </form>
</div>

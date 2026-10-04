<x-guest-layout title="Register" description="Join NutriTrace to track honest food.">
    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div class="space-y-2">
            <april:label for="name">Name</april:label>
            <april:input id="name" type="text" name="name" :value="old('name')" placeholder="Marie Dupont" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div class="space-y-2">
            <april:label for="email">Email</april:label>
            <april:input id="email" type="email" name="email" :value="old('email')" placeholder="you@example.com" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="space-y-2">
            <april:label for="role">Role</april:label>
            <select id="role" name="role" class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" required>
                <option value="consumer" {{ old('role') == 'consumer' ? 'selected' : '' }}>Consumer</option>
                <option value="producer" {{ old('role') == 'producer' ? 'selected' : '' }}>Producer</option>
                <option value="processor" {{ old('role') == 'processor' ? 'selected' : '' }}>Processor</option>
                <option value="distributor" {{ old('role') == 'distributor' ? 'selected' : '' }}>Distributor</option>
            </select>
            <x-input-error :messages="$errors->get('role')" />
        </div>

        <div class="space-y-2">
            <april:label for="password">Password</april:label>
            <april:input id="password" type="password" name="password" placeholder="••••••••" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="space-y-2">
            <april:label for="password_confirmation">Confirm password</april:label>
            <april:input id="password_confirmation" type="password" name="password_confirmation" placeholder="••••••••" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <div class="flex items-center justify-between gap-3 pt-1">
            <april:button-link href="{{ route('login') }}" variant="link" size="sm">Already registered?</april:button-link>

            <april:button type="submit" class="ms-auto">Register</april:button>
        </div>
    </form>
</x-guest-layout>

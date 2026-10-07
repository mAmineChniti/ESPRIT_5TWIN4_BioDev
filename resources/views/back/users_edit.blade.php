@extends('layouts.back')

@section('title', 'Edit User')

@section('content')
<div class="mb-6 flex items-center gap-4">
    <april:button-link href="{{ route('admin.users') }}" variant="link" size="sm" class="text-muted-foreground">
        <x-lucide-arrow-left class="size-4" />
        Back to users
    </april:button-link>
    <h1 class="text-2xl font-bold">Edit User: {{ $user->name }}</h1>
</div>

<april:card class="max-w-2xl">
    <x-slot:title class="text-lg">User Profile</x-slot:title>
    <x-slot:description>Update the account details and role for this user.</x-slot:description>
    <x-slot:content>
        <form action="{{ route('admin.users.update', $user) }}" method="POST" class="space-y-6">
            @csrf
            @method('PATCH')

            <div>
                <april:label for="name">Full Name</april:label>
                <april:input id="name" name="name" :value="old('name', $user->name)" required
                             autocomplete="name" class="mt-1" aria-describedby="name-error"
                             :aria-invalid="$errors->has('name') ? 'true' : 'false'" />
                <x-input-error id="name-error" :messages="$errors->get('name')" class="mt-1" />
            </div>

            <div>
                <april:label for="email">Email Address</april:label>
                <april:input id="email" name="email" type="email" :value="old('email', $user->email)" required
                             autocomplete="username" class="mt-1" aria-describedby="email-error"
                             :aria-invalid="$errors->has('email') ? 'true' : 'false'" />
                <x-input-error id="email-error" :messages="$errors->get('email')" class="mt-1" />
            </div>

            <div>
                <april:label for="role">Platform Role</april:label>
                <april:native-select id="role" name="role" required class="mt-1"
                                       aria-describedby="role-hint role-error"
                                       :aria-invalid="$errors->has('role') ? 'true' : 'false'">
                    <option value="admin" @selected(old('role', $user->role) === 'admin')>Administrator</option>
                    <option value="producer" @selected(old('role', $user->role) === 'producer')>Producer</option>
                    <option value="processor" @selected(old('role', $user->role) === 'processor')>Processor</option>
                    <option value="distributor" @selected(old('role', $user->role) === 'distributor')>Distributor</option>
                    <option value="consumer" @selected(old('role', $user->role) === 'consumer')>Consumer</option>
                </april:native-select>
                <p id="role-hint" class="mt-2 text-sm text-muted-foreground">
                    Changing a role modifies the user's dashboard and permissions.
                    You cannot change your own role, which would lock the last admin out of this area.
                </p>
                <x-input-error id="role-error" :messages="$errors->get('role')" class="mt-1" />
            </div>

            <div class="flex justify-end gap-3 border-t border-border pt-4">
                <april:button-link href="{{ route('admin.users') }}" variant="outline">Cancel</april:button-link>
                <april:button type="submit">Save Changes</april:button>
            </div>
        </form>
    </x-slot:content>
</april:card>
@endsection
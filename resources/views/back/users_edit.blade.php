@extends('layouts.back')

@section('title', 'Edit User')

@section('content')
<div class="mb-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.users') }}" class="text-muted-foreground hover:text-foreground">← Back to users</a>
        <h1 class="text-2xl font-bold">Edit User: {{ $user->name }}</h1>
    </div>
</div>

<div class="bg-card rounded-lg shadow max-w-2xl border border-border">
    <div class="px-6 py-5 border-b border-border">
        <h3 class="text-lg font-medium leading-6 text-foreground">User Profile</h3>
        <p class="mt-1 text-sm text-muted-foreground">Update the account details and role for this user.</p>
    </div>
    
    <div class="px-6 py-5">
        <form action="{{ route('admin.users.update', $user) }}" method="POST" class="space-y-6">
            @csrf
            @method('PATCH')

            <div>
                <label for="name" class="block text-sm font-medium text-foreground">Full Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                    class="mt-1 block w-full rounded-md border-input bg-background shadow-sm focus:border-primary focus:ring-primary sm:text-sm px-3 py-2 border">
                @error('name') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-foreground">Email Address</label>
                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                    class="mt-1 block w-full rounded-md border-input bg-background shadow-sm focus:border-primary focus:ring-primary sm:text-sm px-3 py-2 border">
                @error('email') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="role" class="block text-sm font-medium text-foreground">Platform Role</label>
                <select id="role" name="role" required class="mt-1 block w-full rounded-md border-input bg-background shadow-sm focus:border-primary focus:ring-primary sm:text-sm px-3 py-2 border">
                    <option value="admin" @selected(old('role', $user->role) === 'admin')>Administrator</option>
                    <option value="producer" @selected(old('role', $user->role) === 'producer')>Producer</option>
                    <option value="processor" @selected(old('role', $user->role) === 'processor')>Processor</option>
                    <option value="distributor" @selected(old('role', $user->role) === 'distributor')>Distributor</option>
                    <option value="consumer" @selected(old('role', $user->role) === 'consumer')>Consumer</option>
                </select>
                <p class="mt-2 text-sm text-muted-foreground">Changing a role modifies the user's dashboard and permissions.</p>
                @error('role') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-border">
                <a href="{{ route('admin.users') }}" class="px-4 py-2 border border-input rounded-md text-sm font-medium text-foreground bg-background hover:bg-muted">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-primary-foreground bg-primary hover:bg-primary/90">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

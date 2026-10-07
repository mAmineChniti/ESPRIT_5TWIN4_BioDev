@extends('layouts.back')

@section('title', 'Manage Users')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold">Manage Users</h1>
        <p class="text-sm text-muted-foreground mt-1">All registered users on NutriTrace — {{ $users->count() }} total.</p>
    </div>
</div>

<div class="bg-card rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-border">
        <thead class="bg-muted">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Email</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Role</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Registered</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-card divide-y divide-border">
            @forelse($users as $user)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-full bg-primary/10 flex items-center justify-center font-bold text-primary">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <span class="text-sm font-medium text-foreground">{{ $user->name }}</span>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ $user->email }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs font-bold rounded-full uppercase tracking-wider
                        @if($user->role === 'admin') bg-destructive/10 text-destructive
                        @elseif($user->role === 'producer') bg-primary/10 text-primary
                        @elseif($user->role === 'processor') bg-primary/10 text-primary
                        @elseif($user->role === 'distributor') bg-secondary/50 text-secondary-foreground
                        @else bg-muted text-foreground @endif">
                        {{ $user->role }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">
                    {{ $user->created_at?->format('d M Y') ?? 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                    <a href="{{ route('admin.users.edit', $user) }}" class="text-primary hover:underline">Edit</a>
                    @if(auth()->id() !== $user->id)
                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-destructive hover:underline">Delete</button>
                    </form>
                    @else
                        <span class="text-muted-foreground opacity-50 cursor-not-allowed">Delete</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="px-6 py-4 text-center text-sm text-muted-foreground">No users found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Summary by role --}}
<div class="mt-6 grid grid-cols-2 md:grid-cols-5 gap-4">
    @foreach(['admin','producer','processor','distributor','consumer'] as $role)
    <div class="bg-card rounded-lg shadow p-4 text-center">
        <p class="text-2xl font-bold">{{ $users->where('role', $role)->count() }}</p>
        <p class="text-xs text-muted-foreground uppercase font-semibold mt-1">{{ $role }}s</p>
    </div>
    @endforeach
</div>
@endsection

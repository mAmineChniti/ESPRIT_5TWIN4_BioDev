@extends('layouts.back')

@section('title', 'Manage Users')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold">Manage Users</h1>
    <p class="mt-1 text-sm text-muted-foreground">
        All registered users on NutriTrace — {{ $users->total() }} total.
    </p>
</div>

<april:data-table>
    <x-slot:header>
        <tr>
            <th scope="col" class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Name</th>
            <th scope="col" class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Email</th>
            <th scope="col" class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Role</th>
            <th scope="col" class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Registered</th>
            <th scope="col" class="h-10 px-4 text-right align-middle font-medium text-muted-foreground">Actions</th>
        </tr>
    </x-slot:header>

    <x-slot:body>
        @forelse($users as $user)
            <tr class="border-b transition-colors last:border-0 hover:bg-muted/50">
                <td class="whitespace-nowrap p-4 align-middle">
                    <div class="flex items-center gap-3">
                        <april:avatar size="sm">
                            <x-slot:fallback class="bg-primary text-xs font-bold text-primary-foreground">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </x-slot:fallback>
                        </april:avatar>
                        <span class="text-sm font-medium text-foreground">{{ $user->name }}</span>
                    </div>
                </td>
                <td class="whitespace-nowrap p-4 align-middle text-sm text-muted-foreground">{{ $user->email }}</td>
                <td class="whitespace-nowrap p-4 align-middle">
                    @php
                        [$variant, $extra] = match ($user->role) {
                            'admin' => ['destructive', ''],
                            'producer', 'processor' => ['none', 'bg-primary/10 text-primary'],
                            'distributor' => ['none', 'bg-secondary text-secondary-foreground'],
                            default => ['secondary', ''],
                        };
                    @endphp
                    <april:badge variant="{{ $variant }}" class="uppercase tracking-wider {{ $extra }}">
                        {{ $user->role }}
                    </april:badge>
                </td>
                <td class="whitespace-nowrap p-4 align-middle text-sm text-muted-foreground">
                    {{ $user->created_at?->format('d M Y') ?? 'N/A' }}
                </td>
                <td class="whitespace-nowrap p-4 text-right align-middle">
                    <div class="inline-flex items-center gap-2">
                        <april:button-link href="{{ route('admin.users.edit', $user) }}" variant="link" size="sm">
                            Edit
                        </april:button-link>

                        @if(auth()->id() !== $user->id)
                            <april:alert-dialog>
                                <x-slot:trigger>
                                    <april:button type="button" variant="link" size="sm"
                                                  class="text-destructive">
                                        Delete
                                    </april:button>
                                </x-slot:trigger>
                                <x-slot:content>
                                    <div>
                                        <h2 class="text-lg font-semibold" x-bind="title">Delete this user?</h2>
                                        <p class="mt-2 text-sm text-muted-foreground" x-bind="description">
                                            <strong>{{ $user->name }}</strong> will lose access immediately, along
                                            with their reviews, meals and recorded supply chain steps.
                                        </p>
                                    </div>
                                    <april:alert-dialog-footer>
                                        <april:alert-dialog-cancel>Cancel</april:alert-dialog-cancel>
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" novalidate>
                                            @csrf
                                            @method('DELETE')
                                            <april:button type="submit" variant="destructive" x-bind="action">
                                                Delete user
                                            </april:button>
                                        </form>
                                    </april:alert-dialog-footer>
                                </x-slot:content>
                            </april:alert-dialog>
                        @else
                            {{-- Explain the disabled state rather than showing a
                                 dead control with no reason attached. --}}
                            <april:tooltip>
                                <x-slot:trigger>
                                    <span class="cursor-not-allowed text-sm text-muted-foreground opacity-50"
                                          aria-disabled="true">Delete</span>
                                </x-slot:trigger>
                                <x-slot:content>You cannot delete your own account.</x-slot:content>
                            </april:tooltip>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="p-4 text-center align-middle text-sm text-muted-foreground">
                    No users found.
                </td>
            </tr>
        @endforelse
    </x-slot:body>
</april:data-table>

<div class="mt-6">{{ $users->links() }}</div>

{{-- Summary by role. Counted across the whole table, not just this page. --}}
<div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-5">
    @foreach(['admin', 'producer', 'processor', 'distributor', 'consumer'] as $role)
        <april:card class="text-center">
            <x-slot:content>
                <p class="text-2xl font-bold">{{ $roleCounts[$role] ?? 0 }}</p>
                <p class="mt-1 text-xs font-semibold uppercase text-muted-foreground">{{ $role }}s</p>
            </x-slot:content>
        </april:card>
    @endforeach
</div>
@endsection
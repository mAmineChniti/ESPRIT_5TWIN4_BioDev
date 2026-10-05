@extends('layouts.back')

@section('title', 'Manage Users')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold">Manage Users</h1>
        <p class="text-sm text-muted-foreground mt-1">All registered users on NutriTrace — {{ $users->count() }} total.</p>
    </div>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registered</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($users as $user)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-full bg-indigo-100 flex items-center justify-center font-bold text-indigo-600">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <span class="text-sm font-medium text-gray-900">{{ $user->name }}</span>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->email }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs font-bold rounded-full uppercase tracking-wider
                        @if($user->role === 'admin') bg-red-100 text-red-700
                        @elseif($user->role === 'producer') bg-green-100 text-green-700
                        @elseif($user->role === 'processor') bg-blue-100 text-blue-700
                        @elseif($user->role === 'distributor') bg-yellow-100 text-yellow-700
                        @else bg-gray-100 text-gray-700 @endif">
                        {{ $user->role }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ $user->created_at?->format('d M Y') ?? 'N/A' }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No users found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Summary by role --}}
<div class="mt-6 grid grid-cols-2 md:grid-cols-5 gap-4">
    @foreach(['admin','producer','processor','distributor','consumer'] as $role)
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-2xl font-bold">{{ $users->where('role', $role)->count() }}</p>
        <p class="text-xs text-gray-500 uppercase font-semibold mt-1">{{ $role }}s</p>
    </div>
    @endforeach
</div>
@endsection

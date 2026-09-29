<x-guest-layout title="Verify your email" description="Click the link we just emailed you to get started.">
    @if (session('status') == 'verification-link-sent')
        <april:alert title="Link sent" class="mb-4">
            <x-slot:description>A new verification link has been sent to your email address.</x-slot:description>
        </april:alert>
    @endif

    <div class="flex items-center justify-between gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <april:button type="submit" variant="outline">Resend email</april:button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <april:button type="submit" variant="link">Log out</april:button>
        </form>
    </div>
</x-guest-layout>

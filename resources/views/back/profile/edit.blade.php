@extends('layouts.back')

@section('title', 'Profile')

@section('content')
    <april:breadcrumb>
        <x-slot:list>
            @auth
                @if(auth()->user()->isAdmin())
                    <april:breadcrumb-item>
                        <april:breadcrumb-link href="{{ route('admin.dashboard') }}">Admin</april:breadcrumb-link>
                    </april:breadcrumb-item>
                    <april:breadcrumb-separator />
                @endif
            @endauth
            <april:breadcrumb-item>
                <april:breadcrumb-page>Profile</april:breadcrumb-page>
            </april:breadcrumb-item>
        </x-slot:list>
    </april:breadcrumb>

    <div class="grid max-w-3xl gap-6">
        <april:card>
            <x-slot:title>Profile information</x-slot:title>
            <x-slot:description>Update your name and email address.</x-slot:description>
            <x-slot:content>
                @include('back.profile.partials.update-profile-information-form')
            </x-slot:content>
        </april:card>

        <april:card>
            <x-slot:title>Update password</x-slot:title>
            <x-slot:description>Use a long, random password to stay secure.</x-slot:description>
            <x-slot:content>
                @include('back.profile.partials.update-password-form')
            </x-slot:content>
        </april:card>

        <april:card>
            <x-slot:title>Delete account</x-slot:title>
            <x-slot:description>Permanently remove your account and data.</x-slot:description>
            <x-slot:content>
                @include('back.profile.partials.delete-user-form')
            </x-slot:content>
        </april:card>
    </div>
@endsection

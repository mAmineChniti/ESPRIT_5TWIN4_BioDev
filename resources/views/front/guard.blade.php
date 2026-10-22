@extends('layouts.front')

@section('title', __('Guard page'))

@section('content')
    <div class="mx-auto max-w-3xl space-y-6 px-4 py-12">
        <h1 class="text-3xl font-bold tracking-tight text-foreground">{{ __('BioDev — 5TWIN4') }}</h1>
        <p class="text-muted-foreground">
            {{ __('Group BioDev from the class 5TWIN4.') }}
        </p>

        <april:card>
            <x-slot:content>
                <h2 class="mb-3 text-xl font-semibold">{{ __('Team members') }}</h2>
                <ul class="space-y-2">
                    <li>Mohamed Amine Chniti</li>
                    <li>Mohamed Amine Souibgui</li>
                    <li>Wiem Karaja</li>
                    <li>Hiba Khadhraoui</li>
                    <li>Montaha Trabelsi</li>
                </ul>
            </x-slot:content>
        </april:card>
    </div>
@endsection
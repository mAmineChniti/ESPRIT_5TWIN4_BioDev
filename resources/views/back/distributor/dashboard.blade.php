{{-- back.distributor.dashboard: thin route wrapper over the shared dashboard body. --}}
@extends('layouts.back')

@section('title', 'Dashboard')

@section('content')
    @include('back.partials.dashboard')
@endsection

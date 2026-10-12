@extends('layouts.back')

@section('title', __('Edit Food'))

@section('content')
<x-food-form :food="$food" :selected-certifications="$selectedCertifications" />
@endsection

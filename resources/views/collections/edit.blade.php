@extends('layouts.app')
@section('page-title', 'Edit Collection')
@section('content')
<form method="POST" action="{{ route('collections.update', $collection) }}" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    @method('PUT')
    @include('collections._form')
</form>
@endsection

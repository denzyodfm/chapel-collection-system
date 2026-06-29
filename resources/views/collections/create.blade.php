@extends('layouts.app')
@section('page-title', 'Add Collection')
@section('content')
<form method="POST" action="{{ route('collections.store') }}" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    @include('collections._form')
</form>
@endsection

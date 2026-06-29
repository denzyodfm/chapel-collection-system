@extends('layouts.app')
@section('page-title', 'Add Hugpong Banay')
@section('content')
<form method="POST" action="{{ route('hugpong-banays.store') }}" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    @include('hugpong-banays._form')
</form>
@endsection

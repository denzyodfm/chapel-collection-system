@extends('layouts.app')
@section('page-title', 'Edit User')
@section('content')
<form method="POST" action="{{ route('users.update', $user) }}" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    @method('PUT')
    @include('users._form')
</form>
@endsection

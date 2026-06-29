@extends('layouts.app')
@section('page-title', 'Add User')
@section('content')
<form method="POST" action="{{ route('users.store') }}" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">@include('users._form')</form>
@endsection

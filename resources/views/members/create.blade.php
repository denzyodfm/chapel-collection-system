@extends('layouts.app')
@section('page-title', 'Add Member')
@section('content')
<form method="POST" action="{{ route('members.store') }}" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    @include('members._form')
</form>
@endsection

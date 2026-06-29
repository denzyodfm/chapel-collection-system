@extends('layouts.app')
@section('page-title', 'Edit Member')
@section('content')
<form method="POST" action="{{ route('members.update', $member) }}" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    @method('PUT')
    @include('members._form')
</form>
@endsection

@extends('layouts.app')
@section('page-title', 'Edit Hugpong Banay')
@section('content')
<form method="POST" action="{{ route('hugpong-banays.update', $hugpongBanay) }}" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    @method('PUT')
    @include('hugpong-banays._form')
</form>
@endsection

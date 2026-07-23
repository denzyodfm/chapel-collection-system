@extends('layouts.app')
@section('title', 'Templates | Fatima Chapel')
@section('eyebrow', 'Chapel Documents')
@section('page-title', 'Templates')

@section('content')
@if (auth()->user()->role === 'admin')
    <section class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4">
            <h2 class="text-lg font-bold text-sky-950">Upload a chapel template</h2>
            <p class="mt-1 text-sm text-slate-500">Upload any document type, up to 50 MB per file.</p>
        </div>
        <form method="POST" action="{{ route('templates.store') }}" enctype="multipart/form-data" class="grid gap-4 lg:grid-cols-2">
            @csrf
            <label class="grid gap-1 text-sm font-semibold text-slate-700">
                Template name
                <input name="name" value="{{ old('name') }}" required maxlength="255" class="rounded-lg border border-slate-300 px-4 py-3 font-normal focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100" placeholder="e.g. Mass Sponsor Form">
            </label>
            <label class="grid gap-1 text-sm font-semibold text-slate-700">
                Document
                <input type="file" name="document" required class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 font-normal file:mr-4 file:rounded-md file:border-0 file:bg-sky-50 file:px-3 file:py-1 file:font-semibold file:text-sky-800">
            </label>
            <label class="grid gap-1 text-sm font-semibold text-slate-700 lg:col-span-2">
                Description <span class="font-normal text-slate-400">(optional)</span>
                <textarea name="description" rows="2" maxlength="2000" class="rounded-lg border border-slate-300 px-4 py-3 font-normal focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100" placeholder="Briefly describe when this template should be used.">{{ old('description') }}</textarea>
            </label>
            <div class="lg:col-span-2">
                <button class="rounded-lg bg-sky-800 px-5 py-3 text-sm font-semibold text-white hover:bg-sky-900">Upload Template</button>
            </div>
        </form>
    </section>
@endif

<section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 px-5 py-4">
        <h2 class="font-bold text-sky-950">Available templates</h2>
        <p class="mt-1 text-sm text-slate-500">Open a document in your browser or download a copy.</p>
    </div>

    @forelse ($templates as $template)
        <article class="flex flex-col gap-4 border-b border-slate-100 p-5 last:border-b-0 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <h3 class="font-semibold text-slate-900">{{ $template->name }}</h3>
                <p class="mt-1 break-all text-sm text-slate-600">{{ $template->original_filename }}</p>
                @if ($template->description)
                    <p class="mt-2 text-sm text-slate-500">{{ $template->description }}</p>
                @endif
                <p class="mt-2 text-xs text-slate-400">
                    {{ number_format($template->file_size / 1024, 1) }} KB
                    · Uploaded {{ $template->created_at->format('M j, Y') }}
                    @if ($template->uploader) by {{ $template->uploader->name }} @endif
                </p>
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-3">
                <a href="{{ route('templates.view', $template) }}" target="_blank" rel="noopener" class="rounded-lg border border-sky-200 px-4 py-2 text-sm font-semibold text-sky-800 hover:bg-sky-50">View</a>
                <a href="{{ route('templates.download', $template) }}" class="rounded-lg bg-sky-800 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-900">Download</a>
                @if (auth()->user()->role === 'admin')
                    <form method="POST" action="{{ route('templates.destroy', $template) }}" onsubmit="return confirm('Delete this template?')">
                        @csrf @method('DELETE')
                        <button class="rounded-lg border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Delete</button>
                    </form>
                @endif
            </div>
        </article>
    @empty
        <div class="px-5 py-12 text-center text-sm text-slate-500">No chapel templates have been uploaded yet.</div>
    @endforelse
</section>

<div class="mt-5">{{ $templates->links() }}</div>
@endsection

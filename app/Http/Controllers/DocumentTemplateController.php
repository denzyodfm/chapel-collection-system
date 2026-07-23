<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentTemplateController extends Controller
{
    public function index(): View
    {
        return view('templates.index', [
            'templates' => DocumentTemplate::with('uploader')->latest()->paginate(15),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'document' => ['required', 'file', 'max:51200'],
        ]);

        $file = $request->file('document');
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid().($extension !== '' ? '.'.$extension : '');
        $path = $file->storeAs('document-templates', $filename, 'local');

        if ($path === false) {
            return back()->withInput()->with('error', 'The document could not be stored. Please try again.');
        }

        DocumentTemplate::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);

        return redirect()->route('templates.index')->with('success', 'Template uploaded successfully.');
    }

    public function view(DocumentTemplate $template): BinaryFileResponse
    {
        abort_unless(Storage::disk('local')->exists($template->stored_path), 404);

        $response = response()->file(Storage::disk('local')->path($template->stored_path), [
            'Content-Type' => $template->mime_type ?: 'application/octet-stream',
            'Content-Security-Policy' => 'sandbox',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        $response->setContentDisposition('inline', $template->original_filename);

        return $response;
    }

    public function download(DocumentTemplate $template): BinaryFileResponse
    {
        abort_unless(Storage::disk('local')->exists($template->stored_path), 404);

        return response()->download(
            Storage::disk('local')->path($template->stored_path),
            $template->original_filename,
            ['X-Content-Type-Options' => 'nosniff'],
        );
    }

    public function destroy(DocumentTemplate $template): RedirectResponse
    {
        Storage::disk('local')->delete($template->stored_path);
        $template->delete();

        return redirect()->route('templates.index')->with('success', 'Template deleted.');
    }
}

<?php

namespace Tests\Feature;

use App\Models\DocumentTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_a_template(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('templates.store'), [
                'name' => 'Mass Sponsor Form',
                'description' => 'For chapel mass sponsors.',
                'document' => UploadedFile::fake()->create(
                    'mass-sponsor.docx',
                    24,
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ),
            ])
            ->assertRedirect(route('templates.index'));

        $template = DocumentTemplate::firstOrFail();
        $this->assertSame('mass-sponsor.docx', $template->original_filename);
        $this->assertSame($admin->id, $template->uploaded_by);
        Storage::disk('local')->assertExists($template->stored_path);
    }

    public function test_all_authenticated_roles_can_list_view_and_download_templates(): void
    {
        Storage::fake('local');
        $template = DocumentTemplate::create([
            'name' => 'Chapel Schedule',
            'original_filename' => 'schedule.txt',
            'stored_path' => 'document-templates/schedule.txt',
            'mime_type' => 'text/plain',
            'file_size' => 16,
        ]);
        Storage::disk('local')->put($template->stored_path, 'Chapel schedule');

        foreach (['admin', 'treasurer', 'viewer'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('templates.index'))
                ->assertOk()
                ->assertSee('Chapel Schedule')
                ->assertSee('schedule.txt');
            $this->actingAs($user)->get(route('templates.view', $template))
                ->assertOk()
                ->assertHeader('content-disposition', 'inline; filename=schedule.txt');
            $this->actingAs($user)->get(route('templates.download', $template))
                ->assertOk()
                ->assertDownload('schedule.txt');
        }
    }

    public function test_only_admin_can_upload_or_delete_templates(): void
    {
        Storage::fake('local');
        $viewer = User::factory()->create(['role' => 'viewer']);
        $template = DocumentTemplate::create([
            'name' => 'Protected Template',
            'original_filename' => 'protected.pdf',
            'stored_path' => 'document-templates/protected.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 10,
        ]);
        Storage::disk('local')->put($template->stored_path, 'PDF content');

        $this->actingAs($viewer)->post(route('templates.store'), [
            'name' => 'Unauthorized',
            'document' => UploadedFile::fake()->create('file.pdf', 10, 'application/pdf'),
        ])->assertForbidden();

        $this->actingAs($viewer)
            ->delete(route('templates.destroy', $template))
            ->assertForbidden();

        $this->assertDatabaseHas('document_templates', ['id' => $template->id]);
        Storage::disk('local')->assertExists($template->stored_path);
    }

    public function test_admin_can_delete_a_template_and_its_file(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $template = DocumentTemplate::create([
            'name' => 'Old Template',
            'original_filename' => 'old.pdf',
            'stored_path' => 'document-templates/old.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 10,
        ]);
        Storage::disk('local')->put($template->stored_path, 'PDF content');

        $this->actingAs($admin)
            ->delete(route('templates.destroy', $template))
            ->assertRedirect(route('templates.index'));

        $this->assertDatabaseMissing('document_templates', ['id' => $template->id]);
        Storage::disk('local')->assertMissing($template->stored_path);
    }
}

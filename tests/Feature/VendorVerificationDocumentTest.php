<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorVerificationDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VendorVerificationDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_uploads_private_document_and_admin_reviews_it(): void
    {
        Storage::fake('local');
        config(['marketplace.private_disk' => 'local']);
        $provider = User::factory()->create(['email_verified_at' => now()]);
        $vendor = Vendor::create([
            'user_id' => $provider->id,
            'display_name' => 'Proveedor documental',
            'slug' => 'proveedor-documental',
            'status' => 'active',
        ]);
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole(Role::findOrCreate('admin'));

        $this->actingAs($provider)->post(route('verification-documents.store'), [
            'type' => VendorVerificationDocument::TYPE_GOVERNMENT_ID,
            'document' => UploadedFile::fake()->create('identificacion.pdf', 200, 'application/pdf'),
        ])->assertRedirect();

        $document = $vendor->verificationDocuments()->firstOrFail();
        Storage::disk('local')->assertExists($document->path);
        $this->assertSame(VendorVerificationDocument::STATUS_PENDING, $document->status);
        $this->assertNotEmpty($document->sha256);

        $this->actingAs($admin)->patch(route('admin.verification-documents.review', $document), [
            'decision' => VendorVerificationDocument::STATUS_APPROVED,
            'review_note' => 'Documento legible y datos contrastados.',
        ])->assertRedirect();

        $this->assertSame(VendorVerificationDocument::STATUS_APPROVED, $document->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'vendor.document_approved',
            'subject_id' => $document->id,
        ]);
    }

    public function test_unrelated_user_cannot_download_private_document(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $vendor = Vendor::create([
            'user_id' => $owner->id,
            'display_name' => 'Proveedor privado',
            'slug' => 'proveedor-privado',
            'status' => 'active',
        ]);
        $document = $vendor->verificationDocuments()->create([
            'uploaded_by_user_id' => $owner->id,
            'type' => VendorVerificationDocument::TYPE_PROOF_OF_ADDRESS,
            'status' => VendorVerificationDocument::STATUS_PENDING,
            'disk' => 'local',
            'path' => 'verification-documents/test/domicilio.pdf',
            'original_name' => 'domicilio.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
            'sha256' => hash('sha256', 'test'),
        ]);

        Storage::disk('local')->put($document->path, 'private');

        $this->actingAs(User::factory()->create(['email_verified_at' => now()]))
            ->get(route('verification-documents.download', $document))
            ->assertForbidden();
    }
}

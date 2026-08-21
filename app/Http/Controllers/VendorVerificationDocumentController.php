<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\VendorVerificationDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VendorVerificationDocumentController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $vendor = $request->user()->vendor;
        abort_unless($vendor, 422, 'Primero configura tu perfil comercial.');
        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(VendorVerificationDocument::TYPES))],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);
        $disk = (string) config('marketplace.private_disk', 'local');
        $file = $request->file('document');
        $hash = hash_file('sha256', $file->getRealPath());
        $path = $file->store('verification-documents/'.$vendor->id, $disk);
        throw_unless($path, new \RuntimeException('No fue posible almacenar el documento.'));

        DB::transaction(function () use ($vendor, $request, $validated, $file, $disk, $path, $hash): void {
            $vendor->verificationDocuments()->where('type', $validated['type'])->whereIn('status', ['pending', 'rejected'])->update(['status' => 'superseded']);
            $vendor->verificationDocuments()->create([
                'uploaded_by_user_id' => $request->user()->id,
                'type' => $validated['type'], 'status' => 'pending', 'disk' => $disk, 'path' => $path,
                'original_name' => basename((string) $file->getClientOriginalName()),
                'mime_type' => (string) $file->getMimeType(), 'size' => $file->getSize(), 'sha256' => $hash,
            ]);
        });

        return back()->with('status', 'Documento recibido y protegido. Administración deberá revisarlo.');
    }

    public function download(Request $request, VendorVerificationDocument $document): StreamedResponse
    {
        $isOwner = $document->vendor()->where('user_id', $request->user()->id)->exists();
        abort_unless($isOwner || $request->user()->hasAnyRole(['admin', 'superadmin']), 403);
        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        return Storage::disk($document->disk)->download($document->path, $document->original_name, [
            'Cache-Control' => 'private, no-store, max-age=0', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function review(Request $request, VendorVerificationDocument $document): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'superadmin']), 403);
        abort_if($document->vendor->user_id === $request->user()->id, 403);
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'review_note' => ['required', 'string', 'min:10', 'max:1000'],
        ]);
        abort_unless($document->status === 'pending', 422, 'El documento ya fue revisado.');
        $document->update([
            'status' => $validated['decision'], 'reviewed_by_user_id' => $request->user()->id,
            'review_note' => $validated['review_note'], 'reviewed_at' => now(),
        ]);
        AuditLog::create([
            'user_id' => $request->user()->id, 'action' => 'vendor.document_'.$validated['decision'],
            'subject_type' => VendorVerificationDocument::class, 'subject_id' => $document->id,
            'metadata' => ['vendor_id' => $document->vendor_id, 'type' => $document->type],
            'ip_address' => $request->ip(), 'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000), 'created_at' => now(),
        ]);

        return back()->with('status', 'Documento revisado y decisión registrada en auditoría.');
    }
}
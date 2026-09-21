<?php

namespace App\Http\Controllers;

use App\Models\MessageAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ChatAttachmentController extends Controller
{
    public function __invoke(Request $request, MessageAttachment $attachment)
    {
        Gate::forUser($request->user())->authorize('view', $attachment->message->conversation);
        abort_unless(in_array($attachment->mime_type, ['image/jpeg', 'image/png', 'image/webp'], true), 404);

        return Storage::disk($attachment->disk)->response($attachment->path, 'imagen', [
            'Content-Type' => $attachment->mime_type, 'Cache-Control' => 'no-store, private', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

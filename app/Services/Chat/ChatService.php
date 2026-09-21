<?php

namespace App\Services\Chat;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Events\UserTyping;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessageReceipt;
use App\Models\User;
use App\Notifications\ChatMessageReceived;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ChatService
{
    public function send(Conversation $conversation, User $user, string $body, string $token, array $images = []): Message
    {
        Gate::forUser($user)->authorize('view', $conversation);
        $body = trim($body);
        Validator::make(compact('body', 'token', 'images'), [
            'body' => ['nullable', 'string', 'max:2000', 'required_without:images'], 'token' => ['required', 'uuid'],
            'images' => ['array', 'max:'.config('chat.max_images')],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('chat.max_image_kb'), 'dimensions:max_width=3000,max_height=3000'],
        ])->validate();
        abort_if(RateLimiter::tooManyAttempts('chat:send:'.$user->id, 60), 429);
        RateLimiter::hit('chat:send:'.$user->id, 60);
        $stored = [];
        try {
            return DB::transaction(function () use ($conversation, $user, $body, $token, $images, &$stored): Message {
                $locked = Conversation::lockForUpdate()->findOrFail($conversation->id);
                Gate::forUser($user)->authorize('view', $locked);
                $existing = $locked->messages()->where('sender_id', $user->id)->where('client_message_id', $token)->first();
                if ($existing) {
                    return $existing;
                }
                abort_unless($locked->acceptsMessages(), 422, 'Este chat ya no acepta mensajes.');
                $message = $locked->messages()->create(['sender_id' => $user->id, 'body' => $body,
                    'type' => $images ? 'image' : 'text', 'client_message_id' => $token]);
                foreach ($images as $image) {
                    // Re-encode raster data: no EXIF/GPS or active SVG content is retained.
                    if (! function_exists('imagecreatefromstring')) {
                        throw ValidationException::withMessages(['images' => 'El servidor necesita GD para procesar imágenes de forma privada.']);
                    }
                    $bitmap = @imagecreatefromstring(file_get_contents($image->getRealPath()));
                    if (! $bitmap) {
                        throw ValidationException::withMessages(['images' => 'No se pudo procesar la imagen.']);
                    }
                    ob_start();
                    try {
                        imagepng($bitmap);
                        $bytes = ob_get_contents();
                    } finally {
                        ob_end_clean();
                        imagedestroy($bitmap);
                    }
                    if (strlen($bytes) > 10 * 1024 * 1024) {
                        throw ValidationException::withMessages(['images' => 'La imagen procesada supera el tamaño permitido.']);
                    }
                    $path = 'chat/'.$locked->public_id.'/'.Str::uuid().'.png';
                    $disk = config('chat.disk');
                    throw_unless(Storage::disk($disk)->put($path, $bytes, ['visibility' => 'private']), new \RuntimeException('No se pudo guardar la imagen.'));
                    $stored[] = [$disk, $path];
                    $size = getimagesizefromstring($bytes);
                    $message->attachments()->create(['disk' => $disk, 'path' => $path, 'original_name' => 'imagen.png',
                        'mime_type' => 'image/png', 'size_bytes' => strlen($bytes), 'width' => $size[0], 'height' => $size[1]]);
                }
                $locked->update(['last_message_at' => now()]);
                $participants = $locked->participants()->get();
                foreach ($participants as $recipient) {
                    if ($recipient->id === $user->id) {
                        continue;
                    }
                    $message->receipts()->create(['user_id' => $recipient->id]);
                    $muted = $recipient->pivot->muted_until && now()->parse($recipient->pivot->muted_until)->isFuture();
                    if ($recipient->canUseMarketplace() && ! $muted && ! Cache::has($this->viewingKey($locked, $recipient))) {
                        $recipient->notify(new ChatMessageReceived($locked->public_id, $message->id));
                    }
                }
                $this->afterCommit(new MessageSent($locked->public_id, $message->id, $participants->modelKeys()));

                return $message;
            });
        } catch (Throwable $exception) {
            foreach ($stored as [$disk, $path]) {
                if (! MessageAttachment::where('disk', $disk)->where('path', $path)->exists()) {
                    Storage::disk($disk)->delete($path);
                }
            }
            throw $exception;
        }
    }

    public function acknowledge(Conversation $conversation, User $user, int $throughId, bool $read): void
    {
        Gate::forUser($user)->authorize('view', $conversation);
        DB::transaction(function () use ($conversation, $user, $throughId, $read): void {
            $locked = Conversation::lockForUpdate()->findOrFail($conversation->id);
            Gate::forUser($user)->authorize('view', $locked);
            $through = $locked->messages()->findOrFail($throughId);
            $changed = false;
            $locked->messages()->where('id', '<=', $throughId)
                ->where(fn ($q) => $q->where('sender_id', '!=', $user->id)->orWhereNotNull('admin_user_id'))
                ->select('id')->chunkById(200, function ($messages) use ($user, $read, &$changed): void {
                    $rows = $messages->map(fn ($message) => ['message_id' => $message->id, 'user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()])->all();
                    MessageReceipt::insertOrIgnore($rows);
                    $query = MessageReceipt::where('user_id', $user->id)->whereIn('message_id', $messages->modelKeys());
                    $changed = (clone $query)->whereNull('delivered_at')->update(['delivered_at' => now(), 'updated_at' => now()]) > 0 || $changed;
                    if ($read) {
                        $changed = (clone $query)->whereNull('read_at')->update(['read_at' => now(), 'updated_at' => now()]) > 0 || $changed;
                    }
                });
            if ($read) {
                DB::table('conversation_participants')->where('conversation_id', $locked->id)->where('user_id', $user->id)
                    ->where(fn ($q) => $q->whereNull('last_read_message_id')->orWhere('last_read_message_id', '<', $throughId))
                    ->update(['last_read_message_id' => $throughId, 'last_read_at' => $through->created_at, 'updated_at' => now()]);
                $user->unreadNotifications()->where('data->kind', 'chat_message')->where('data->conversation_id', $locked->public_id)
                    ->where('data->message_id', '<=', $throughId)->update(['read_at' => now()]);
            }
            if ($changed) {
                $this->afterCommit(new MessageRead($locked->public_id, $user->id, $throughId, $read));
            }
        });
    }

    public function viewing(Conversation $conversation, User $user, bool $visible): void
    {
        Gate::forUser($user)->authorize('view', $conversation);
        if ($visible) {
            Cache::put($this->viewingKey($conversation, $user), true, 35);
        } else {
            Cache::forget($this->viewingKey($conversation, $user));
        }
    }

    public function typing(Conversation $conversation, User $user): void
    {
        Gate::forUser($user)->authorize('send', $conversation);
        $key = 'chat:typing:'.$conversation->id.':'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 1)) {
            return;
        }
        RateLimiter::hit($key, 3);
        $this->afterCommit(new UserTyping($conversation->public_id, $user->id, now()->addSeconds(5)->timestamp));
    }

    private function viewingKey(Conversation $conversation, User $user): string
    {
        return 'chat:viewing:'.$conversation->id.':'.$user->id;
    }

    private function afterCommit(object $event): void
    {
        DB::afterCommit(function () use ($event): void {
            try {
                event($event);
            } catch (Throwable $exception) {
                // Persisted messages remain recoverable even if the queue/broadcaster is down.
                report($exception);
            }
        });
    }
}

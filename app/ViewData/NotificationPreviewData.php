<?php

namespace App\ViewData;

use Illuminate\Notifications\DatabaseNotification;

final readonly class NotificationPreviewData
{
    public function __construct(
        public string $id,
        public string $category,
        public string $context,
        public string $title,
        public string $body,
        public bool $isRead,
        public string $createdAtLabel,
    ) {}

    public static function from(DatabaseNotification $notification): self
    {
        $kind = (string) ($notification->data['kind'] ?? 'activity');

        return new self(
            id: $notification->id,
            category: str_starts_with($kind, 'social_') ? 'social' : 'administrative',
            context: match (true) {
                str_contains($kind, 'dispute') => 'dispute',
                str_contains($kind, 'vendor') => 'verification',
                str_contains($kind, 'report'), str_contains($kind, 'removed') => 'report',
                str_contains($kind, 'support'), str_contains($kind, 'conversation'), str_contains($kind, 'comment') => 'message',
                str_contains($kind, 'like') => 'like',
                str_contains($kind, 'share') => 'share',
                str_contains($kind, 'follow') => 'follow',
                default => 'system',
            },
            title: (string) ($notification->data['title'] ?? 'Actividad nueva'),
            body: (string) ($notification->data['body'] ?? ''),
            isRead: $notification->read_at !== null,
            createdAtLabel: $notification->created_at->diffForHumans(),
        );
    }
}

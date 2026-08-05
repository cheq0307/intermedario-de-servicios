<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MarketplaceActivity extends Notification
{
    use Queueable;

    /** @param array<string, int|string> $routeParameters */
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly string $routeName,
        public readonly array $routeParameters = [],
        public readonly string $kind = 'activity',
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'route_name' => $this->routeName,
            'route_parameters' => $this->routeParameters,
            'kind' => $this->kind,
        ];
    }
}

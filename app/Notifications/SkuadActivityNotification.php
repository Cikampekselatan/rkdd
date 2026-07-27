<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SkuadActivityNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $kind,
        private readonly string $title,
        private readonly string $url,
        private readonly ?string $body = null,
        private readonly array $meta = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => $this->kind,
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            ...$this->meta,
        ];
    }
}

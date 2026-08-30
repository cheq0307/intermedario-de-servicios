<?php

namespace App\ViewData;

use App\Domain\Marketplace\Enums\SupportTicketStatus;
use App\Models\SupportTicket;

final readonly class SupportThreadData
{
    /** @param array<string, string> $statuses */
    public function __construct(
        public bool $isAdmin,
        public bool $canReply,
        public int $lastMessageId,
        public array $statuses,
    ) {}

    public static function from(SupportTicket $ticket, bool $isAdmin): self
    {
        return new self(
            isAdmin: $isAdmin,
            canReply: $ticket->status !== SupportTicketStatus::Closed,
            lastMessageId: (int) ($ticket->messages->max('id') ?? 0),
            statuses: SupportTicketStatus::labels(),
        );
    }
}

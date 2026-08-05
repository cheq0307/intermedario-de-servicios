<?php

namespace App\Domain\Marketplace\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case AwaitingPayment = 'awaiting_payment';
    case Paid = 'paid';
    case InProgress = 'in_progress';
    case Ready = 'ready';
    case Delivered = 'delivered';
    case Completed = 'completed';
    case Disputed = 'disputed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, match ($this) {
            self::Pending => [self::Accepted, self::Cancelled],
            self::Accepted => [self::AwaitingPayment, self::Paid, self::InProgress, self::Cancelled],
            self::AwaitingPayment => [self::Paid, self::Cancelled],
            self::Paid => [self::InProgress, self::Ready, self::Refunded, self::Disputed],
            self::InProgress => [self::Ready, self::Delivered, self::Disputed, self::Cancelled],
            self::Ready => [self::Delivered, self::Disputed, self::Cancelled],
            self::Delivered => [self::Completed, self::Disputed],
            self::Disputed => [self::InProgress, self::Delivered, self::Completed, self::Refunded, self::Cancelled],
            self::Completed, self::Cancelled, self::Refunded => [],
        }, true);
    }
}

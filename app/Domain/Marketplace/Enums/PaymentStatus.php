<?php

namespace App\Domain\Marketplace\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Authorized = 'authorized';
    case Paid = 'paid';
    case ReleasePending = 'release_pending';
    case Released = 'released';
    case RefundPending = 'refund_pending';
    case Refunded = 'refunded';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}

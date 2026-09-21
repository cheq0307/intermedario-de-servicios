<?php

namespace App\Domain\Marketplace\Enums;

enum MessageDeliveryStatus: string
{
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Read = 'read';
}
